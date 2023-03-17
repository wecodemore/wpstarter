<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use Composer\Composer;
use WeCodeMore\WpStarter\ComposerPlugin;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Step\Step;
use WeCodeMore\WpStarter\Step\WpCliCommandsStep;

class SelectedStepsFactory
{
    public const MODE_COMMAND = 16;
    public const MODE_LIST = 32;
    public const MODE_OPT_OUT = 1;
    public const SKIP_CUSTOM_STEPS = 2;
    public const IGNORE_SKIP_STEPS_CONFIG = 4;

    /**
     * @var bool
     */
    private $commandMode;

    /**
     * @var bool
     */
    private $listMode;

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
     * @var array<string>
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
     * @param string $alias
     * @param list<string> $validNames
     * @return string|null
     */
    public static function findStepNameByAlias(string $alias, array $validNames): ?string
    {
        if (in_array($alias, $validNames, true)) {
            return $alias;
        }

        $altNames = [];
        $noSpecial = preg_replace('/[^a-zA-Z0-9]/', '', $alias) ?? '';
        $noSpecialValid = ($noSpecial !== '') && ($noSpecial !== $alias);
        $noSpecialValid and $altNames[] = $noSpecial;
        if (strpos($alias, 'build') === 0) {
            $noBuild = preg_replace('/^[^a-zA-Z0-9]+/', '', substr($alias, 5)) ?? '';
            $altNames[] = $noBuild;
            $noBuildNoSpecial = $noSpecialValid ? substr($noSpecial, 5) : '';
            ($noBuildNoSpecial !== '') and $altNames[] = $noBuildNoSpecial;
        }

        foreach ($altNames as $altName) {
            if (in_array($altName, $validNames, true)) {
                return $altName;
            }
        }

        return null;
    }

    /**
     * @param int $flags
     * @param string ...$stepNames
     */
    final public function __construct(int $flags = 0, string ...$stepNames)
    {
        $this->commandMode = $this->checkFlag($flags, self::MODE_COMMAND);
        $this->listMode = $this->checkFlag($flags, self::MODE_LIST);
        $this->commandStepNames = ($this->listMode || $this->commandMode) ? $stepNames : [];

        $this->optOutMode = ($this->commandMode || $this->listMode)
            && $this->checkFlag($flags, self::MODE_OPT_OUT);

        $this->skipCustomSteps = ($this->commandMode || $this->listMode)
            && $this->checkFlag($flags, self::SKIP_CUSTOM_STEPS);

        $this->ignoreSkipConfig = ($this->commandMode || $this->listMode)
            && $this->checkFlag($flags, self::IGNORE_SKIP_STEPS_CONFIG);
    }

    /**
     * @return bool
     */
    public function isSelectedCommandMode(): bool
    {
        return $this->commandStepNames && !$this->optOutMode;
    }

    /**
     * @return bool
     */
    public function isListMode(): bool
    {
        return $this->listMode;
    }

    /**
     * @return bool
     */
    public function isFullRun(): bool
    {
        return !($this->isSelectedCommandMode() || $this->isListMode());
    }

    /**
     * @param Locator $locator
     * @param Composer $composer
     * @return array<Step>
     */
    public function selectAndFactory(Locator $locator, Composer $composer): array
    {
        $this->inputErrors = 0;
        $this->configErrors = 0;
        $this->emptyOptOutInput = false;
        $this->maybeWantIgnoreConfig = 0;

        $io = $locator->io();
        $config = $locator->config();
        $availableSteps = $this->availableStepsNameToClassMap($config, $io);

        if ($this->listMode) {
            $this->printCommandList($io, $availableSteps, $config);

            return [];
        }

        if (!$availableSteps) {
            return [];
        }

        $stepsToFactory = $availableSteps;
        if ($this->isSelectedCommandMode()) {
            $stepsToFactory = $this->selectedStepsNameToClassMap($availableSteps, $io);
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
     * @param int $flags
     * @param int $flag
     * @return bool
     */
    private function checkFlag(int $flags, int $flag): bool
    {
        return ($flags & $flag) === $flag;
    }

    /**
     * @param Config $config
     * @param Io $io
     * @return array<string, class-string<Step>>
     */
    private function availableStepsNameToClassMap(Config $config, Io $io): array
    {
        /** @var array<string, class-string<Step>> $defaultSteps */
        $defaultSteps = ComposerPlugin::defaultSteps();
        /** @var array<string, string> $customSteps */
        $customSteps = $config[Config::CUSTOM_STEPS]->unwrapOrFallback([]);
        /** @var array<string, string> $commandSteps */
        $commandSteps = $config[Config::COMMAND_STEPS]->unwrapOrFallback([]);

        $targetSteps = ($this->skipCustomSteps || !$customSteps)
            ? $defaultSteps
            : array_merge($defaultSteps, $customSteps);

        if ($commandSteps && ($this->isListMode() || $this->isSelectedCommandMode())) {
            $targetSteps = array_merge($targetSteps, $commandSteps);
        }

        $targetSteps = $this->filterOutSkippedSteps($config, $targetSteps, $io);
        $availableStepClassesMap = $this->filterOutInvalidSteps($targetSteps);

        return $availableStepClassesMap;
    }

    /**
     * @param array $allSteps
     * @return array<string, class-string<Step>>
     */
    private function filterOutInvalidSteps(array $allSteps): array
    {
        $errors = 0;

        /** @var array<string, class-string<Step>> $stepClassesMap */
        $stepClassesMap = array_filter(
            $allSteps,
            static function (string $step) use (&$errors): bool {
                if (!is_a($step, Step::class, true)) {
                    /** @psalm-suppress MixedOperand */
                    $errors++;

                    return false;
                }

                return true;
            }
        );

        /** @psalm-suppress MixedOperand */
        $this->configErrors += $errors;

        return $stepClassesMap;
    }

    /**
     * @param Config $config
     * @param array<string, string> $allAvailableStepNameToClassMap
     * @param Io $io
     * @return array<string, string>
     */
    private function filterOutSkippedSteps(
        Config $config,
        array $allAvailableStepNameToClassMap,
        Io $io
    ): array {

        // In opt-out mode, steps to opt-out are required
        if ($this->optOutMode && !$this->commandStepNames) {
            $this->emptyOptOutInput = true;

            return [];
        }

        $skipNamesByInput = $this->optOutMode ? $this->commandStepNames : [];
        /** @var array<string> $skipNamesByConfig */
        $skipNamesByConfig = $this->ignoreSkipConfig
            ? []
            : $config[Config::SKIP_STEPS]->unwrapOrFallback([]);

        if (!$skipNamesByInput && !$skipNamesByConfig) {
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
            if ($skipNamesByConfig && in_array($name, $skipNamesByConfig, true)) {
                $skippedByConfig++;
                $skipped = true;
                ($this->commandMode || $this->listMode) or $io->writeIfVerbose(
                    "- Step '{$name}' will be skipped: disabled in config."
                );

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
        $this->configErrors += count($skipNamesByConfig) - $skippedByConfig;

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
     * @param array<string, class-string<Step>> $availableStepNameToClassMap
     * @param Io $io
     * @return array<string, class-string<Step>>
     */
    private function selectedStepsNameToClassMap(array $availableStepNameToClassMap, Io $io): array
    {
        // When opt-out mode, $allAvailableStepNameToClassMap have been already filtered-out from
        // selected steps in `filterOutSkippedSteps`
        if ($this->optOutMode) {
            return $availableStepNameToClassMap;
        }

        $validCommandStepNamesToClasses = [];

        foreach ($this->commandStepNames as $name) {
            [$stepName, $stepClass] = $this->findStepName($name, $availableStepNameToClassMap, $io);
            if (($stepName !== null) && ($stepClass !== null)) {
                $validCommandStepNamesToClasses[$stepName] = $stepClass;
                continue;
            }

            $this->inputErrors ++;
        }

        return $validCommandStepNamesToClasses;
    }

    /**
     * @param string $name
     * @param array<string, class-string<Step>> $availableStepsMap
     * @param Io $io
     * @return array{string, class-string<Step>}|array{null, null}
     */
    private function findStepName(string $name, array $availableStepsMap, Io $io): array
    {
        $stepName = static::findStepNameByAlias($name, array_keys($availableStepsMap));
        if ($stepName) {
            if ($stepName !== $name) {
                $comment = "Step name '{$name}' is deprecated, please use '{$stepName}'.";
                $io->writeCommentIfVerbose($comment);
            }

            return [$stepName, $availableStepsMap[$stepName]];
        }

        return [null, null];
    }

    /**
     * @param array<string, class-string<Step>> $stepsToFactory
     * @param Locator $locator
     * @param Composer $composer
     * @return array<Step>
     */
    private function factory(array $stepsToFactory, Locator $locator, Composer $composer): array
    {
        $wpCliSteps = [];
        $factored = [];

        foreach ($stepsToFactory as $stepName => $stepClass) {
            try {
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
            if (
                is_a($stepClass, WpCliCommandsStep::class, true)
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
     * @param Io $io
     * @param array<string, class-string<Step>> $availableSteps
     * @param Config $config
     * @return void
     */
    private function printCommandList(Io $io, array $availableSteps, Config $config): void
    {
        /** @var array<string, string> $commandSteps */
        $commandSteps = $config[Config::COMMAND_STEPS]->unwrapOrFallback([]);

        $io->write("Usage:");
        $io->write("$ composer <comment>wpstarter</comment> <info>\<command\>...</info>");
        $io->write('');
        $io->write('Available commands:');
        $io->write('');
        ksort($availableSteps, \SORT_STRING);
        foreach ($availableSteps as $name => $class) {
            $suffix = isset($commandSteps[$name]) ? '*' : '';
            $io->write("<info>{$name}</info>{$suffix}");
            $ref = new \ReflectionClass($class);
            $desc = "The '{$name}' step command.";
            if (preg_match('~/\*\*\s*(?:\*\s*)?([^\*]+)~', $ref->getDocComment() ?: '', $matches)) {
                $desc = trim(rtrim($matches[1], '\\*'));
            }
            $io->write($desc);
            $io->write('');
        }
        if ($commandSteps) {
            $io->write('<comment>* Command only</comment>');
            $io->write('');
        }

        $this->lastError();
        $this->printExclusionInList($io, $config);
    }

    /**
     * @param Io $io
     * @param Config $config
     * @return void
     */
    private function printExclusionInList(Io $io, Config $config): void
    {
        $skipByInput = $this->optOutMode ? $this->commandStepNames : null;
        /** @var null|array $skipByConfig */
        $skipByConfig = $this->ignoreSkipConfig
            ? null
            : $config[Config::SKIP_STEPS]->unwrapOrFallback([]);
        if (!$skipByInput && !$skipByConfig) {
            return;
        }

        $message = 'Please note %d step%s not included because %s';
        $args = [];
        if ($skipByConfig) {
            $count = count($skipByConfig);
            $args[] = $count;
            $args[] = ($count === 1) ? ' is' : 's are';
            $args[] = "excluded in project's composer.json";
        }
        if ($skipByInput) {
            $skipByConfig and $message .= ', moreover %d step%s not included because %s';
            $count = count($skipByInput);
            $args[] = $count;
            $args[] = ($count === 1) ? ' is' : 's are';
            $args[] = 'skipped via command';
        }

        $io->write(vsprintf("{$message}.", $args));
    }

    /**
     * @param bool $fatal
     * @return string
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    private function lastErrorMessage(bool $fatal): string
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity
        if ($this->maybeWantIgnoreConfig) {
            $error = ($this->inputErrors > 1)
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
            $error = ($this->inputErrors > 1)
                ? "Command input contains {$this->inputErrors} invalid steps names"
                : 'Command input contains one invalid step name';
            if (!$fatal) {
                $error .= ($this->inputErrors > 1)
                    ? ', they will be ignored.'
                    : ' and it will be ignored.';
            }

            $message .= $fatal ? "\n{$error}." : $error;
        }

        if ($this->emptyOptOutInput) {
            return "{$message}\nCommand input was expecting one or more step names.";
        }

        if ($this->configErrors) {
            $also = $this->inputErrors ? 'also ' : '';
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
