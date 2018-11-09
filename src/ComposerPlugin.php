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
use WeCodeMore\WpStarter\Util\Io;

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
     * @return array
     */
    public static function defaultSteps(): array
    {
        return [
            Step\CheckPathStep::NAME => Step\CheckPathStep::class,
            Step\WpConfigStep::NAME => Step\WpConfigStep::class,
            Step\IndexStep::NAME => Step\IndexStep::class,
            Step\FlushEnvCacheStep::NAME => Step\FlushEnvCacheStep::class,
            Step\MuLoaderStep::NAME => Step\MuLoaderStep::class,
            Step\EnvExampleStep::NAME => Step\EnvExampleStep::class,
            Step\DropinsStep::NAME => Step\DropinsStep::class,
            Step\MoveContentStep::NAME => Step\MoveContentStep::class,
            Step\ContentDevStep::NAME => Step\ContentDevStep::class,
            Step\WpCliConfigStep::NAME => Step\WpCliConfigStep::class,
            Step\WpCliCommandsStep::NAME => Step\WpCliCommandsStep::class,
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
     * @param bool $skipMode
     * @return void
     * @throws \Exception
     */
    public function run(Event $event = null, array $selectedStepNames = [], bool $skipMode = false)
    {
        $this->setupAutoload();

        $filesystem = new Filesystem();
        $requirements = new Util\Requirements($this->composer, $this->io, $filesystem);
        $config = $requirements->config();

        $autoload = $config[Config::AUTOLOAD]->unwrapOrFallback();
        if ($autoload && is_file($autoload)) {
            require_once $autoload;
        }

        $this->locator = new Util\Locator($requirements, $this->composer, $this->io, $filesystem);

        try {
            $requireWp = $config[Config::REQUIRE_WP]->not(false);
            $fallbackVer = $config[Config::WP_VERSION]->unwrapOrFallback('');
            $wpVersion = $this->checkWp($requireWp, $fallbackVer, $config);
            if (!$wpVersion && $requireWp) {
                $event or exit(1);

                return;
            }

            ($event === null && $selectedStepNames) or $this->logo();

            $skipDbCheck = $config[Config::SKIP_DB_CHECK];
            if ($skipDbCheck->notEmpty() && $skipDbCheck->not(true)) {
                $this->locator->dbChecker()->check();
            }

            $steps = $this->initializeSteps($config, $event, $skipMode, ...$selectedStepNames);
            $steps->run($this->locator->config(), $this->locator->paths());

            $event or exit(0);
        } catch (\Throwable $throwable) {
            $lines = [$throwable->getMessage()];
            if ($this->io->isVerbose()) {
                $lines = explode("\n", $throwable->getTraceAsString());
                array_unshift($lines, '');
                array_unshift($lines, $throwable->getMessage());
            }

            $this->locator->io()->writeErrorBlock(...$lines);

            $event or exit(1);
        }
    }

    /**
     * @param bool $requireWp
     * @param string $fallbackVer
     * @param Config $config
     * @return string
     */
    private function checkWp(bool $requireWp, string $fallbackVer, Config $config): string
    {
        $wpVersion = '';
        if ($requireWp) {
            $wpVersionDiscover = new Util\WpVersion(
                $this->locator->packageFinder(),
                $this->locator->io(),
                $fallbackVer
            );
            $wpVersion = $wpVersionDiscover->discover();
        }

        if (!$wpVersion && $requireWp) {
            return '';
        }

        // If WP version found and no version is in configs, let's set it with the finding.
        if ($wpVersion && !$fallbackVer) {
            $config[Config::WP_VERSION] = $fallbackVer;
        }

        return $wpVersion;
    }

    /**
     * @param Config $config
     * @param Event $event
     * @param bool $skipMode
     * @param string[] $selectedStepNames
     * @return Step\Steps
     * @throws \Exception
     */
    private function initializeSteps(
        Config $config,
        Event $event = null,
        bool $skipMode = false,
        string ...$selectedStepNames
    ): Step\Steps {

        $io = $this->locator->io();
        $commandMode = $event === null && $selectedStepNames;
        $errorsIn = [];

        $steps = new Step\Steps($this->locator, $this->composer, $commandMode);

        $selectedStepNames = array_filter($selectedStepNames, 'is_string');
        $defaultSteps = static::defaultSteps();
        $customSteps = $config[Config::CUSTOM_STEPS]->unwrapOrFallback([]);
        $allSteps = array_unique(array_merge($defaultSteps, $customSteps));

        if ($commandMode && !$skipMode) {
            $commandStepClasses = $config[Config::COMMAND_STEPS]->unwrapOrFallback();
            if ($commandStepClasses) {
                $allSteps = array_unique(array_merge($allSteps, $commandStepClasses));
            }
        }

        if ($commandMode) {
            $invalidSelectedNames = $this->checkSelectedSteps($allSteps, $selectedStepNames);
            $errorsIn['input'] = $invalidSelectedNames;
        }

        $skippedClasses = $this->classesToSkip($config, $allSteps, $selectedStepNames, $skipMode);
        $targetStepClasses = $skippedClasses ? array_diff($allSteps, $skippedClasses) : $allSteps;
        $skipMode and $selectedStepNames = [];

        if (!$targetStepClasses) {
            $io->writeColorBlock('yellow', 'Nothing to run.');

            return $steps;
        }

        $failedFactory = $this->factorySteps($steps, $targetStepClasses, $selectedStepNames);
        $errorsIn['config'] = $failedFactory;

        if (!array_filter($errorsIn)) {
            return $steps;
        }

        if (!count($steps)) {
            throw new \Exception($this->errorsInMessage($errorsIn, true));
        }

        $text = Io::ensureLength($this->errorsInMessage($errorsIn, false));
        $io->writeErrorLine('');
        array_walk($text, [$io, 'writeErrorLine']);
        $io->writeErrorLine('');

        return $steps;
    }

    /**
     * @param Config $config
     * @param array $allStepClasses
     * @param array $selectedStepNames
     * @param bool $skipMode
     * @return array
     */
    private function classesToSkip(
        Config $config,
        array $allStepClasses,
        array $selectedStepNames,
        bool $skipMode
    ): array {

        $skippedStepClasses = $config[Config::SKIP_STEPS]->unwrapOrFallback([]);
        if (!$skipMode || !$selectedStepNames) {
            return $skippedStepClasses;
        }

        foreach ($allStepClasses as $name => $class) {
            if (in_array($name, $selectedStepNames, true)
                && !in_array($class, $skippedStepClasses, true)
            ) {
                $skippedStepClasses[] = $class;
            }
        }

        return $skippedStepClasses;
    }

    /**
     * @param Step\Steps $steps
     * @param array $targetStepClasses
     * @param array $selectedStepNames
     * @return int
     */
    private function factorySteps(
        Step\Steps $steps,
        array $targetStepClasses,
        array $selectedStepNames
    ): int {

        $stepsAdded = [];
        $wpCliSteps = [];

        $errors = 0;

        foreach ($targetStepClasses as $stepName => $stepClass) {
            if (!$stepName
                || in_array($stepName, $stepsAdded, true)
                || ($selectedStepNames && !in_array($stepName, $selectedStepNames, true))
            ) {
                $stepName or $errors++;

                continue;
            }

            $step = $this->factoryStep($stepClass);
            if ($step->name() !== $stepName) {
                $errors++;

                continue;
            }

            $stepsAdded[] = $stepName;

            if (is_a($stepClass, Step\WpCliCommandsStep::class, true)
                || ($stepName === Step\WpCliCommandsStep::NAME)
            ) {
                $wpCliSteps[] = $step;
                continue;
            }

            $steps->addStep($step);
        }

        if ($wpCliSteps) {
            $steps->addStep(...$wpCliSteps);
        }

        return $errors;
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
     * @param array $allSteps
     * @param array $selectedStepNames
     * @return int
     */
    private function checkSelectedSteps(array $allSteps, array $selectedStepNames): int
    {
        $invalid = 0;
        foreach ($selectedStepNames as $selectedStepName) {
            array_key_exists($selectedStepName, $allSteps) or $invalid++;
        }

        return $invalid;
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

    /**
     * WP Starter is required from Composer, which means it is deployed with WordPress, and so
     * any autoloading setting that WP Starter declares will "pollute" the Composer autoloader that
     * is loaded at every WordPress request.
     * For this reason we keep in the autoload section of composer.json only the needed bare-minimum
     * (basically this class) then we register a simple PSR-4 loader for the rest.
     *
     * @return void
     */
    private function setupAutoload()
    {
        spl_autoload_register(
            function (string $class) {
                if (stripos($class, __NAMESPACE__) === 0) {
                    $file = substr(str_replace('\\', '/', $class), strlen(__NAMESPACE__)) . '.php';
                    require_once __DIR__ . $file;
                }
            },
            true,
            true
        );
    }

    /**
     * @param array $errorsIn
     * @param bool $fatal
     * @return string
     */
    private function errorsInMessage(array $errorsIn, bool $fatal): string
    {
        $inputErrors = $errorsIn['input'] ?? 0;
        $configErrors = $errorsIn['config'] ?? 0;

        if (!$inputErrors && !$configErrors) {
            return '';
        }

        $message = $fatal ? 'No valid step to run found.' : '';

        if ($inputErrors) {
            $error = $inputErrors > 1
                ? "Command input contains {$inputErrors} invalid steps names"
                : 'Command input contains one invalid step name';
            if (!$fatal) {
                $error .= $inputErrors > 1
                    ? ', they will be ignored.'
                    : ' and it will be ignored.';
            }

            $message .= $fatal ? "\n{$error}." : $error;
        }

        if ($configErrors) {
            $also = $inputErrors ? 'also ' : '';
            $error = $configErrors > 1
                ? "Custom steps config {$also}contains {$inputErrors} invalid steps classes"
                : "Custom steps config {$also}contains one invalid step class";

            if (!$fatal) {
                $error .= $configErrors > 1
                    ? ', they will be ignored'
                    : ' and it will be ignored';
                $error .= $inputErrors ? 'as well.' : '.';
            }

            $message .= $fatal ? "\n{$error}." : "\n{$error}";
        }

        return trim($message);
    }
}
