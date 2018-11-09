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
use WeCodeMore\WpStarter\Step\NullStep;
use WeCodeMore\WpStarter\Step\Step;
use WeCodeMore\WpStarter\Step\WpCliCommandsStep;

/**
 * @package WeCodeMore\WpStarter\Util
 */
class SelectedStepsFactory
{
    const SKIP = 1;
    const SKIP_CUSTOM = 2;
    const IGNORE_SKIP_CONFIG = 4;
    const MODE_COMMAND = 16;

    /**
     * @var bool
     */
    private $commandMode;

    /**
     * @var bool
     */
    private $skip;

    /**
     * @var bool
     */
    private $skipCustom;

    /**
     * @var bool
     */
    private $ignoreSkipConfig;

    /**
     * @var string[]
     */
    private $names;

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
    private $emptyInput = false;

    /**
     * @return SelectedStepsFactory
     */
    public static function autorun(): SelectedStepsFactory
    {
        return new static();
    }

    /**
     * @param int $flags
     * @param string[] $stepNames
     */
    public function __construct(int $flags = 0, string ...$stepNames)
    {
        $this->commandMode = ($flags & self::MODE_COMMAND) === self::MODE_COMMAND;
        if (!$this->commandMode) {
            $this->skip = false;
            $this->skipCustom = false;
            $this->ignoreSkipConfig = false;
            $this->names = [];
        }

        $this->skip = ($flags & self::SKIP) === self::SKIP;
        $this->skipCustom = ($flags & self::SKIP_CUSTOM) === self::SKIP_CUSTOM;
        $this->ignoreSkipConfig = ($flags & self::IGNORE_SKIP_CONFIG) === self::IGNORE_SKIP_CONFIG;
        $this->names = $stepNames;
    }

    /**
     * @return bool
     */
    public function isSelectedCommandMode(): bool
    {
        return $this->names && !$this->skip;
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
        $this->emptyInput = false;

        $config = $locator->config();

        $defaultSteps = ComposerPlugin::defaultSteps();
        $customSteps = $config[Config::CUSTOM_STEPS]->unwrap();
        $commandSteps = $config[Config::COMMAND_STEPS]->unwrap();

        $selectedCommandMode = $this->isSelectedCommandMode();

        $allSteps = $this->skipCustom ? $defaultSteps : array_merge($defaultSteps, $customSteps);
        if ($selectedCommandMode) {
            $allSteps = array_merge($allSteps, $commandSteps);
        }

        $allSteps = $this->filterValidSteps($allSteps);
        if (!$allSteps) {
            return [];
        }

        $validSelected = [];
        if ($this->names) {
            $allSelected = array_fill_keys($this->names, 1);
            $validSelected = array_intersect_key($allSelected, $allSteps);
            $this->inputErrors = count($allSelected) - count($validSelected);
        }

        if (($selectedCommandMode || $this->skip) && !$validSelected) {
            $this->inputErrors or $this->emptyInput = true;

            return [];
        }

        $allSteps = array_unique($allSteps);

        if ($this->commandMode && !$this->skip) {
            if (!$selectedCommandMode) {
                return $this->factory($allSteps, $locator, $composer);
            }

            $valid = array_intersect_key($allSteps, $validSelected);

            return $this->factory($valid, $locator, $composer);
        }

        $skippedSteps = $this->stepsToSkip($config, $validSelected, $allSteps);
        $skippedSteps and $allSteps = array_diff_key($allSteps, $skippedSteps);

        if (!$allSteps) {
            return [];
        }

        return $this->factory($allSteps, $locator, $composer);
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
     * @param array $allSteps
     * @return array
     */
    private function filterValidSteps(array $allSteps): array
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
     * @param bool $fatal
     * @return string
     */
    private function lastErrorMessage(bool $fatal): string
    {
        if (!$this->inputErrors && !$this->configErrors) {
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

        if ($this->emptyInput) {
            return "{$message}\nCommand input was expecting one or more step names.";
        }

        if ($this->configErrors) {
            $also = $this->inputErrors ? 'also ' : '';
            $error = $this->configErrors > 1
                ? "Configuration {$also}contains some invalid steps settings"
                : "Configuration {$also}contains one invalid step setting";

            if (!$fatal) {
                $error .= $this->configErrors > 1
                    ? ', they will be ignored'
                    : ' and it will be ignored';
                $error .= $this->inputErrors ? ' as well.' : '.';
            }

            $message .= $fatal ? "\n{$error}." : "\n{$error}";
        }

        return trim($message);
    }

    /**
     * @param Config $config
     * @param array $selected
     * @param array $allSteps
     * @return array
     */
    private function stepsToSkip(Config $config, array $selected, array $allSteps): array
    {
        $skippedNames = [];
        $skippedSteps = $this->ignoreSkipConfig
            ? []
            : $config[Config::SKIP_STEPS]->unwrapOrFallback([]);

        foreach ($skippedSteps as $class) {
            $name = array_search($class, $allSteps, true);
            if ($name === false) {
                $this->configErrors++;
                continue;
            }
            $skippedNames[$name] = 1;
        }

        if (!$this->skip || !$selected) {
            return $skippedNames;
        }

        return array_merge($skippedNames, $selected);
    }

    /**
     * @param array $allSteps
     * @param Locator $locator
     * @param Composer $composer
     * @return array
     */
    private function factory(array $allSteps, Locator $locator, Composer $composer): array
    {
        $wpCliSteps = [];
        $factored = [];

        foreach ($allSteps as $stepName => $stepClass) {
            if (!$stepName) {
                $this->configErrors++;

                continue;
            }

            $step = $this->factoryStep($stepClass, $locator, $composer);
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
     * @param string $stepClass
     * @param Locator $locator
     * @param Composer $composer
     * @return Step
     */
    private function factoryStep(string $stepClass, Locator $locator, Composer $composer): Step
    {
        if (!is_subclass_of($stepClass, Step::class, true)) {
            return new NullStep();
        }

        return new $stepClass($locator, $composer);
    }
}
