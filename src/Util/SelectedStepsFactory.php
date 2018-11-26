<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Composer;
use WeCodeMore\WpStarter\ComposerPlugin;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Step\Step;
use WeCodeMore\WpStarter\Step\WpCliCommandsStep;

class SelectedStepsFactory
{
    const MODE_COMMAND = 16;
    const MODE_OPT_OUT = 1;
    const SKIP_CUSTOM_STEPS = 2;
    const IGNORE_SKIP_STEPS_CONFIG = 4;

    /**
     * @var bool
     */
    private $commandMode;

    /**
     * @var bool
     */
    private $optOutMode;

    /**
     * @var bool
     */
    private $skipCustomSteps;

    /**
     * @var bool
     */
    private $ignoreSkipConfig;

    /**
     * @var string[]
     */
    private $commandStepNames;

    /**
     * @var int
     */
    private $inputErrors = 0;

    /**
     * @var int
     */
    private $configErrors = 0;

    /**
     * @var bool
     */
    private $emptyOptOutInput = false;

    /**
     * @var int
     */
    private $maybeWantIgnoreConfig = 0;

    /**
     * @return SelectedStepsFactory
     */
    public static function autorun(): SelectedStepsFactory
    {
        return new static();
    }

    /**
     * @param int $flags
     * @param string ...$stepNames
     */
    public function __construct(int $flags = 0, string ...$stepNames)
    {
        $this->commandMode = ($flags & self::MODE_COMMAND) === self::MODE_COMMAND;
        if (!$this->commandMode) {
            $this->optOutMode = false;
            $this->skipCustomSteps = false;
            $this->ignoreSkipConfig = false;
            $this->commandStepNames = [];
        }

        $this->commandStepNames = $stepNames;

        $this->optOutMode = ($flags & self::MODE_OPT_OUT)
            === self::MODE_OPT_OUT;
        $this->skipCustomSteps = ($flags & self::SKIP_CUSTOM_STEPS)
            === self::SKIP_CUSTOM_STEPS;
        $this->ignoreSkipConfig = ($flags & self::IGNORE_SKIP_STEPS_CONFIG)
            === self::IGNORE_SKIP_STEPS_CONFIG;
    }

    /**
     * @return bool
     */
    public function isSelectedCommandMode(): bool
    {
        return $this->commandStepNames && !$this->optOutMode;
    }

    /**
     * @param Locator $locator
     * @param Composer $composer
     * @return Step[]
     */
    public function selectAndFactory(Locator $locator, Composer $composer): array
    {
        $this->inputErrors = 0;
        $this->configErrors = 0;
        $this->emptyOptOutInput = false;
        $this->maybeWantIgnoreConfig = 0;

        $availableSteps = $this->availableStepsNameToClassMap($locator->config());

        if (!$availableSteps) {
            return [];
        }

        $stepsToFactory = $availableSteps;
        if ($this->isSelectedCommandMode()) {
            $stepsToFactory = $this->selectedStepsNameToClassMap($availableSteps);
        }

        return $this->factory($stepsToFactory, $locator, $composer);
    }

    /**
     * @return string
     */
    public function lastError(): string
    {
        return $this->lastErrorMessage(false);
    }

    /**
     * @return string
     */
    public function lastFatalError(): string
    {
        return $this->lastErrorMessage(true);
    }

    /**
     * @param Config $config
     * @return array
     */
    private function availableStepsNameToClassMap(Config $config): array
    {
        $defaultSteps = ComposerPlugin::defaultSteps();
        $customSteps = $config[Config::CUSTOM_STEPS]->unwrapOrFallback([]);
        $commandSteps = $config[Config::COMMAND_STEPS]->unwrapOrFallback([]);

        $availableSteps = ($this->skipCustomSteps || !$customSteps)
            ? $defaultSteps
            : array_merge($defaultSteps, $customSteps);

        if ($commandSteps && $this->isSelectedCommandMode()) {
            $availableSteps = array_merge($availableSteps, $commandSteps);
        }

        $availableSteps = $this->filterInValidSteps(
            $this->filterOutSkippedSteps($config, $availableSteps)
        );

        if (!$config[Config::WP_CLI_FILES]->notEmpty()
            && !$config[Config::WP_CLI_COMMANDS]->notEmpty()
        ) {
            unset($availableSteps[WpCliCommandsStep::NAME]);
        }

        return $availableSteps;
    }

    /**
     * @param array $allSteps
     * @return array
     */
    private function filterInValidSteps(array $allSteps): array
    {
        return array_filter(
            $allSteps,
            function (string $step): bool {
                if (!is_a($step, Step::class, true)) {
                    $this->configErrors++;

                    return false;
                }

                return true;
            }
        );
    }

    /**
     * @param Config $config
     * @param array $allAvailableStepNameToClassMap
     * @return array
     */
    private function filterOutSkippedSteps(
        Config $config,
        array $allAvailableStepNameToClassMap
    ): array {

        // In opt-out mode, steps to opt-out are required
        if ($this->optOutMode && !$this->commandStepNames) {
            $this->emptyOptOutInput = true;

            return [];
        }

        $skipNamesByInput = $this->optOutMode ? $this->commandStepNames : [];
        $skipClassesByConfig = $this->ignoreSkipConfig
            ? []
            : $config[Config::SKIP_STEPS]->unwrapOrFallback([]);

        if (!$skipNamesByInput && !$skipClassesByConfig) {
            return $allAvailableStepNameToClassMap;
        }

        $filtered = [];
        $skippedByInput = 0;
        $skippedByConfig = 0;
        $commandStepNames = $this->commandStepNames;
        foreach ($allAvailableStepNameToClassMap as $name => $class) {
            $skipped = false;
            // In explicitly skipped, let's skip it
            if (($skipNamesByInput && in_array($name, $skipNamesByInput, true))) {
                $skippedByInput++;
                $skipped = true;
            }

            // In other cases, let's skip what in skip config (unless ignore-skip config is set)
            if ($skipClassesByConfig && in_array($class, $skipClassesByConfig, true)) {
                $skippedByConfig++;
                $skipped = true;

                // If config say to skip something that was passed explicitly, we have to remove it
                // otherwise we will later try to build it.
                $skippingCommandStep = in_array($name, $commandStepNames, true);
                $skippingCommandStep and $commandStepNames = array_diff($commandStepNames, [$name]);
            }

            if ($skipped) {
                continue;
            }

            $filtered[$name] = $class;
        }

        $this->inputErrors += count($skipNamesByInput) - $skippedByInput;
        $this->configErrors += count($skipClassesByConfig) - $skippedByConfig;

        // If ignoring passed steps because of config, warn user to maybe use ignore config flag
        $countOldCommandStepNames = count($this->commandStepNames);
        $countNewCommandStepNames = count($commandStepNames);
        if ($countOldCommandStepNames !== $countNewCommandStepNames) {
            $this->commandStepNames = $commandStepNames;
            $this->maybeWantIgnoreConfig = $countOldCommandStepNames - $countNewCommandStepNames;
        }

        return $filtered;
    }

    /**
     * @param array $allAvailableStepNameToClassMap
     * @return array
     */
    private function selectedStepsNameToClassMap(array $allAvailableStepNameToClassMap): array
    {
        // When opt-out mode, $allAvailableStepNameToClassMap have been already filtered-out from
        // selected steps in `filterOutSkippedSteps`
        if ($this->optOutMode) {
            return $allAvailableStepNameToClassMap;
        }

        $validCommandStepNamesToClasses = [];

        foreach ($this->commandStepNames as $name) {
            if (!array_key_exists($name, $allAvailableStepNameToClassMap)) {
                $this->inputErrors ++;
                continue;
            }

            $validCommandStepNamesToClasses[$name] = $allAvailableStepNameToClassMap[$name];
        }

        return $validCommandStepNamesToClasses;
    }

    /**
     * @param array<string,string> $stepsToFactory
     * @param Locator $locator
     * @param Composer $composer
     * @return array
     */
    private function factory(array $stepsToFactory, Locator $locator, Composer $composer): array
    {
        $wpCliSteps = [];
        $factored = [];

        foreach ($stepsToFactory as $stepName => $stepClass) {
            try {
                /** @var Step $step */
                $step = new $stepClass($locator, $composer);
            } catch (\Throwable $throwable) {
                $this->configErrors++;
                continue;
            }

            if ($step->name() !== $stepName) {
                $this->configErrors++;

                continue;
            }

            // Make sure WP CLI steps goes at the end.
            if (is_a($stepClass, WpCliCommandsStep::class, true)
                || ($stepName === WpCliCommandsStep::NAME)
            ) {
                $wpCliSteps[] = $step;
                continue;
            }

            $factored[] = $step;
        }

        foreach ($wpCliSteps as $wpCliStep) {
            $factored[] = $wpCliStep;
        }

        return $factored;
    }

    /**
     * @param bool $fatal
     * @return string
     */
    private function lastErrorMessage(bool $fatal): string
    {
        if ($this->maybeWantIgnoreConfig) {
            $error = $this->inputErrors > 1
                ? "{$this->inputErrors} of the given step names have been ignored"
                : 'One given step name has been ignored';

            $error .= ' because ignored via configuration in JSON file.';

            return "{$error}. You might want to use '--ignore-skip-config' flag to avoid that.";
        }

        if (!$this->inputErrors && !$this->configErrors && !$this->emptyOptOutInput) {
            return '';
        }

        $message = $fatal ? 'No valid step to run found.' : '';

        if ($this->inputErrors) {
            $error = $this->inputErrors > 1
                ? "Command input contains {$this->inputErrors} invalid steps names"
                : 'Command input contains one invalid step name';
            if (!$fatal) {
                $error .= $this->inputErrors > 1
                    ? ', they will be ignored.'
                    : ' and it will be ignored.';
            }

            $message .= $fatal ? "\n{$error}." : $error;
        }

        if ($this->emptyOptOutInput) {
            return "{$message}\nCommand input was expecting one or more step names.";
        }

        if ($this->configErrors) {
            $also = ($this->inputErrors || $this->emptyOptOutInput) ? 'also ' : '';
            $error = $this->configErrors > 1
                ? "Configuration {$also}contains {$this->configErrors} invalid steps settings"
                : "Configuration {$also}contains one invalid step setting";

            if (!$fatal) {
                $error .= $this->configErrors > 1
                    ? ', they will be ignored'
                    : ' and it will be ignored';
                $error .= $also ? ' as well.' : '.';
            }

            $message .= $fatal ? "\n{$error}." : "\n{$error}";
        }

        return trim($message);
    }
}
