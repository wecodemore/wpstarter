<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util;
use WeCodeMore\WpStarter\Step;

/**
 * Composer plugin class to run all the WP Starter steps on Composer install or update and also adds
 * 'wpstarter' command to allow doing same thing "on demand".
 */
final class ComposerPlugin implements
    PluginInterface,
    EventSubscriberInterface,
    Capable,
    CommandProvider
{

    const EXTRA_KEY = 'wpstarter';

    const STEP_CLASSES = [
        Step\CheckPathStep::NAME => Step\CheckPathStep::class,
        Step\WpConfigStep::NAME => Step\WpConfigStep::class,
        Step\IndexStep::NAME => Step\IndexStep::class,
        Step\MuLoaderStep::NAME => Step\MuLoaderStep::class,
        Step\EnvExampleStep::NAME => Step\EnvExampleStep::class,
        Step\DropinsStep::NAME => Step\DropinsStep::class,
        Step\MoveContentStep::NAME => Step\MoveContentStep::class,
        Step\ContentDevStep::NAME => Step\ContentDevStep::class,
        Step\WpCliConfigStep::NAME => Step\WpCliConfigStep::class,
    ];

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Util\Locator
     */
    private $locator;

    /**
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors
     */
    public static function getSubscribedEvents(): array
    {
        // phpcs:enable

        return [
            'post-install-cmd' => 'run',
            'post-update-cmd' => 'run',
        ];
    }

    /**
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors
     */
    public function getCapabilities(): array
    {
        // phpcs:enable

        return [CommandProvider::class => __CLASS__];
    }

    /**
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors
     */
    public function getCommands(): array
    {
        // phpcs:enable

        return [new WpStarterCommand()];
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Run WP Starter installation adding all the steps to Builder and launching steps processing.
     *
     * It is possible to provide the names of steps to run.
     *
     * @param Event|null $event
     * @param array $selectedStepNames
     * @return void
     */
    public function run(Event $event = null, array $selectedStepNames = [])
    {
        $filesystem = new Filesystem();
        $requirements = new Util\Requirements($this->composer, $this->io, $filesystem);
        $config = $requirements->config();
        $requireWp = $config[Config::REQUIRE_WP]->not(false);
        $fallbackVer = $config[Config::WP_VERSION]->notEmpty()
            ? $config[Config::WP_VERSION]->unwrap()
            : null;

        $this->locator = new Util\Locator($requirements, $this->composer, $this->io, $filesystem);

        try {
            $wpVersion = null;
            if ($requireWp) {
                $wpVersionDiscover = new Util\WpVersion(
                    $this->locator->packageFinder(),
                    $this->locator->io(),
                    $fallbackVer
                );
                $wpVersion = $wpVersionDiscover->discover();
            }

            if (!$wpVersion && $requireWp) {
                $event or exit(1);

                return;
            }

            // If WP version found and no version is in configs, let's set it with the finding.
            if ($wpVersion && !$fallbackVer) {
                $config->appendConfig(Config::WP_VERSION, $wpVersion);
            }

            $steps = new Step\Steps($this->locator, $this->composer);
            $selectedStepNames = array_filter($selectedStepNames, 'is_string');
            $customSteps = $this->locator->config()[Config::CUSTOM_STEPS]->unwrapOrFallback([]);
            $skippedSteps = $this->locator->config()[Config::SKIP_STEPS]->unwrapOrFallback([]);
            $allSteps = array_unique(array_merge(self::STEP_CLASSES, $customSteps));
            if (!$event) {
                $cmdSteps = $this->locator->config()[Config::CUSTOM_STEPS]->unwrapOrFallback();
                $cmdSteps and $allSteps = array_merge($allSteps, $cmdSteps);
            }

            $stepClasses = array_diff($allSteps, $skippedSteps);
            $hasWpCliStep = false;

            $this->factorySteps($steps, $stepClasses, $selectedStepNames, $hasWpCliStep);
            if (!$hasWpCliStep && $config[Config::WP_CLI_COMMANDS]->notEmpty()) {
                $steps->addStep(new Step\WpCliCommandsStep($this->locator));
            }

            $this->logo();
            $steps->run($this->locator->config(), $this->locator->paths());

            $event or exit(0);
        } catch (\Throwable $throwable) {
            $lines = [$throwable->getMessage()];
            if ($this->io->isVerbose()) {
                $lines[] = (string)$throwable;
            }

            $this->locator->io()->writeErrorBlock(...$lines);

            $event or exit(1);
        }
    }

    /**
     * @param Step\Steps $steps
     * @param array $stepClasses
     * @param array $selectedStepNames
     * @param bool $hasWpCliStep
     */
    private function factorySteps(
        Step\Steps $steps,
        array $stepClasses,
        array $selectedStepNames,
        bool &$hasWpCliStep
    ) {

        $stepsAdded = [];
        $wpCliSteps = [];

        foreach ($stepClasses as $stepName => $stepClass) {
            if (!$stepName
                || ($selectedStepNames && !in_array($stepName, $selectedStepNames, true))
                || in_array($stepName, $stepsAdded, true)
            ) {
                continue;
            }

            $step = $this->factoryStep($stepClass);
            if ($step->name() !== $stepName) {
                continue;
            }

            $stepsAdded[] = $stepName;

            if (is_a($stepClass, Step\WpCliCommandsStep::class, true)
                || (strpos($stepName, Step\WpCliCommandsStep::NAME) === 0)
            ) {
                $wpCliSteps[] = $stepClass;
                continue;
            }

            $steps->addStep($step);
        }

        if ($wpCliSteps) {
            $hasWpCliStep = true;
            $steps->addStep(...$wpCliSteps);
        }
    }

    /**
     * @param string $stepClass
     * @return Step\Step
     */
    private function factoryStep(string $stepClass): Step\Step
    {
        if (!is_subclass_of($stepClass, Step\Step::class, true)) {
            return new Step\NullStep();
        }

        return new $stepClass($this->locator, $this->composer);
    }

    /**
     * @return void
     */
    private function logo()
    {
        // phpcs:disable
        $logo = <<<LOGO
<fg=magenta>    __      __ ___  </><fg=yellow>   ___  _____  _    ___  _____  ___  ___  </>
<fg=magenta>    \ \    / /| _ \ </><fg=yellow>  / __||_   _|/_\  | _ \|_   _|| __|| _ \ </>
<fg=magenta>     \ \/\/ / |  _/ </><fg=yellow>  \__ \  | | / _ \ |   /  | |  | _| |   / </>
<fg=magenta>      \_/\_/  |_|   </><fg=yellow>  |___/  |_|/_/ \_\|_|_\  |_|  |___||_|_\ </>
LOGO;
        // phpcs:enable

        $this->io->write("\n{$logo}\n");
    }
}
