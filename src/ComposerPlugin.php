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
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Script\Event;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util;
use WeCodeMore\WpStarter\Step;

final class ComposerPlugin implements PluginInterface, EventSubscriberInterface, CommandProvider
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
    ];

    /**
     * @var Util\Locator
     */
    private $locator;

    /**
     * @var IOInterface
     */
    private $composerIo;

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
        $wpVersionDiscover = new Util\WpVersion($composer, $io);
        $wpVersion = $wpVersionDiscover->discover();

        // If no or wrong WP ver found do nothing, so run() will show an error not findind config
        if (!$wpVersion) {
            return;
        }

        $this->composerIo = $io;
        $this->locator = new Util\Locator(
            new Util\Requirements($composer, $io, $wpVersion),
            $composer
        );
    }

    /**
     * Run WP Starter installation adding all the steps to Builder and launching steps processing.
     *
     * It is possible to provide the names of steps to run.
     *
     * @param Event|null $event
     * @param array $selectedSteps
     * @return void
     */
    public function run(Event $event = null, array $selectedSteps = [])
    {
        if (!$this->locator) {
            // If here, activate() bailed earlier.
            $this->composerIo->writeError('Error running WP Starter command.');
            $this->composerIo->writeError('WordPress not found or found in a too old version.');

            return;
        }

        $steps = new Step\Steps($this->locator);

        if (!$steps->allowed($this->locator->config(), $this->locator->paths())) {
            $this->locator->io()->block(
                [
                    'WP Starter installation CANCELED.',
                    'wp-config.php was found in root folder and your overwrite settings',
                    'do not allow to proceed.',
                ],
                'yellow'
            );

            return;
        }

        $selectedSteps = array_filter($selectedSteps, 'is_string');
        $customSteps = $this->locator->config()[Config::CUSTOM_STEPS]->unwrapOrFallback([]);
        $stepClasses = array_merge(self::STEP_CLASSES, $customSteps);
        $hasWpCli = false;

        $this->factorySteps($steps, $stepClasses, $selectedSteps, $hasWpCli);
        $this->createExecutor($hasWpCli, $steps, $this->locator->config());
        $this->logo();
        $steps->run($this->locator->config(), $this->locator->paths());
    }

    /**
     * @param Step\Steps $steps
     * @param array $stepClasses
     * @param array $selectedSteps
     * @param bool $hasWpCliStep
     */
    private function factorySteps(
        Step\Steps $steps,
        array $stepClasses,
        array $selectedSteps,
        bool &$hasWpCliStep
    ) {

        $stepsAdded = [];

        foreach ($stepClasses as $stepName => $stepClass) {
            if (!$stepName
                || ($selectedSteps && !in_array($stepName, $selectedSteps, true))
                || in_array($stepName, $stepsAdded, true)
            ) {
                continue;
            }

            $step = $this->factoryStep($stepClass);
            if ($step->name() === $stepName) {
                $stepName === Step\WpCliCommandsStep::NAME and $hasWpCliStep = true;
                $steps->addStep($step);
                $stepsAdded[] = $stepName;
            }
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

        return new $stepClass($this->locator);
    }

    /**
     * @param bool $hasWpCliStep
     * @param Step\Steps $steps
     * @param Config $config
     */
    private function createExecutor(bool $hasWpCliStep, Step\Steps $steps, Config $config)
    {
        if (!$hasWpCliStep && $config[Config::WP_CLI_COMMANDS]->notEmpty()) {
            $steps->addStep(new Step\WpCliCommandsStep($this->locator));
            $hasWpCliStep = true;
        }

        if (!$hasWpCliStep) {
            return;
        }

        $config = $this->locator->config();

        $executorFactory = new WpCli\ExecutorFactory(
            $this->locator->paths(),
            $this->locator->io(),
            $this->locator->urlDownloader(),
            $config,
            $this->locator->composer()
        );

        $wpCliCommand = new WpCli\Command($config, $this->locator->urlDownloader());
        $wpCliExecutor = $executorFactory->create($wpCliCommand);
        $config->appendConfig(Config::WP_CLI_EXECUTOR, $wpCliExecutor);
    }

    /**
     * @return void
     */
    private function logo()
    {
        $magenta = '<fg=magenta>';
        $yellow = ' </><fg=yellow>';
        ob_start();
        ?>

        <?= $magenta ?>__      __ ___ <?= $yellow ?> ___  _____  _    ___  _____  ___  ___  </>
        <?= $magenta ?>\ \    / /| _ \<?= $yellow ?>/ __||_   _|/_\  | _ \|_   _|| __|| _ \ </>
        <?= $magenta ?> \ \/\/ / |  _/<?= $yellow ?>\__ \  | | / _ \ |   /  | |  | _| |   / </>
        <?= $magenta ?>  \_/\_/  |_|  <?= $yellow ?>|___/  |_|/_/ \_\|_|_\  |_|  |___||_|_\ </>

        <?php
        $this->locator->io()->write(ob_get_clean());
    }
}
