<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

use Composer\Composer;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\SelectedStepsFactory;

/**
 * A step whose routine consists in running other steps routines.
 *
 * This is used as main "task runner" for WP Starter.
 *
 * phpcs:disable Inpsyde.CodeQuality.PropertyPerClassLimit
 */
final class Steps implements PostProcessStep, \Countable
{
    /**
     * @var Locator
     */
    private $locator;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var \SplObjectStorage<Step,null>
     */
    private $steps;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Util\OverwriteHelper
     */
    private $overwriteHelper;

    /**
     * @var bool
     */
    private $isCommandMode;

    /**
     * @var \SplObjectStorage<PostProcessStep,null>|null
     */
    private $postProcessSteps = null;

    /**
     * @var array<string, array>|null
     */
    private $scripts = null;

    /**
     * @var int
     */
    private $errors = 0;

    /**
     * @var bool
     */
    private $running = false;

    /**
     * @var string
     */
    private $runningScripts = '';

    /**
     * @var bool
     */
    private $runningSteps = false;

    /**
     * @param Locator $locator
     * @param Composer $composer
     * @return Steps
     */
    public static function composerMode(Locator $locator, Composer $composer): Steps
    {
        return new static($locator, $composer, false);
    }

    /**
     * @param Locator $locator
     * @param Composer $composer
     * @return Steps
     */
    public static function commandMode(Locator $locator, Composer $composer): Steps
    {
        return new static($locator, $composer, true);
    }

    /**
     * @param Locator $locator
     * @param Composer $composer
     * @param bool $isCommandMode
     */
    private function __construct(Locator $locator, Composer $composer, bool $isCommandMode)
    {
        $this->locator = $locator;
        $this->composer = $composer;
        $this->io = $locator->io();
        $this->overwriteHelper = $locator->overwriteHelper();
        $this->steps = new \SplObjectStorage();
        $this->isCommandMode = $isCommandMode;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->steps->count();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'wpstarter';
    }

    /**
     * @param Step $step
     * @param Step ...$steps
     * @return Steps
     */
    public function addStep(Step $step, Step ...$steps): Steps
    {
        if (!$this->running || $this->runningScripts === 'pre') {
            $this->steps->attach($step);
            array_walk($steps, [$this->steps, 'attach']);
        }

        return $this;
    }

    /**
     * @param Step $step
     * @param Step ...$steps
     * @return Steps
     */
    public function removeStep(Step $step, Step ...$steps): Steps
    {
        if (!$this->running || $this->runningScripts === 'pre') {
            $this->steps->detach($step);
            array_walk($steps, [$this->steps, 'detach']);
        }

        return $this;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        return true;
    }

    /**
     * Process all added steps.
     *
     * "pre-wpstarter" script it is last chance to get steps to run added or removed.
     *
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        if ($this->running) {
            return Step::NONE;
        }

        $this->scripts = $config[Config::SCRIPTS]->unwrap();

        $this->running = true;

        $this->runningScripts = 'pre';
        $this->runStepScripts($this, 'pre-');
        $this->runningScripts = '';

        // We check here because "pre" scripts can add or remove steps.
        if (!$this->count()) {
            return Step::NONE;
        }

        $this->steps->rewind();
        $this->runningSteps = true;
        while ($this->steps->valid()) {
            $step = $this->steps->current();

            try {
                $result = $this->runStep($step, $config, $paths);
            } catch (\Throwable $throwable) {
                $this->runningSteps = false;
                throw $throwable;
            }

            if (!$result) {
                $this->runningSteps = false;

                return Step::ERROR;
            }

            $this->steps->next();
        }
        $this->runningSteps = false;

        $this->postProcess($this->io);

        $this->runningScripts = 'post';
        $this->runStepScripts($this, 'post-', Step::SUCCESS);
        $this->runningScripts = '';

        $this->running = false;

        return $this->finalMessage();
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return $this->errors === 1
            ? 'One WP Starter step failed, the project might not be configured properly.'
            : "{$this->errors} WP Starter steps failed, project might not be configured properly.";
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return 'WP Starter finished successfully!';
    }

    /**
     * Runs after all steps have been processed bay calling post process method on all steps.
     *
     * @param Io $io
     * @return void
     */
    public function postProcess(Io $io): void
    {
        if ($this->running || !$this->postProcessSteps) {
            return;
        }

        $this->postProcessSteps->rewind();
        while ($this->postProcessSteps->valid()) {
            $step = $this->postProcessSteps->current();
            $io->writeIfVerbose('Running post-processing for step "' . $step->name() . '"...');
            $step->postProcess($io);
            $this->postProcessSteps->next();
        }
    }

    /**
     * @param Step $step
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    private function runStep(Step $step, Config $config, Paths $paths): bool
    {
        $name = $step->name();
        if (!$name) {
            return true;
        }

        $this->io->writeIfVerbose("- Initializing '{$name}' step.");

        if ($step instanceof PostProcessStep) {
            $this->postProcessSteps or $this->postProcessSteps = new \SplObjectStorage();
            $this->postProcessSteps->attach($step);
        }

        if (!$this->shouldProcess($step, $paths, $config)) {
            return true;
        }

        if (!$this->runStepScripts($step, 'pre-')) {
            return true;
        }

        try {
            $result = $step->run($config, $paths);
        } catch (\Throwable $throwable) {
            $this->printMessages($throwable->getMessage(), true);
            $result = self::ERROR;
        }

        $this->runStepScripts($step, 'post-', $result);

        return $this->continueOnStepResult($step, $result);
    }

    /**
     * @param Step $step
     * @param Paths $paths
     * @param Config $config
     * @return bool
     */
    private function shouldProcess(Step $step, Paths $paths, Config $config): bool
    {
        $name = $step->name();

        $comment = '';
        $process = $step->allowed($config, $paths);
        if (!$process) {
            $reason = ($step instanceof ConditionalStep)
                ? $step->conditionsNotMet()
                : 'requisites not met';
            $comment = sprintf("Step '%s' not executed: %s.", $name, $reason);
        }

        if ($process && ($step instanceof FileCreationStep)) {
            $path = $step->targetPath($paths);
            $process = $this->overwriteHelper->shouldOverwrite($path);
            $comment = $process ? '' : basename($path) . ' exists and will be preserved.';
        }

        $isSelected = $config[Config::IS_WPSTARTER_SELECTED_COMMAND]->is(true);
        if (!$isSelected && $process && ($step instanceof OptionalStep)) {
            $process = $step->askConfirm($config, $this->io);
            $comment = $process ? '' : $step->skipped();
        }

        if ($process) {
            return true;
        }

        $isSelected
            ? $this->io->writeComment($comment)
            : $this->io->writeIfVerbose("  - {$comment}");

        return false;
    }

    /**
     * @param Step $step
     * @param int $result
     * @return bool
     */
    private function continueOnStepResult(Step $step, int $result): bool
    {
        if ($result === Step::SUCCESS) {
            $this->printMessages($step->success(), false);
        }

        if (($result & Step::ERROR) === Step::ERROR) {
            $this->printMessages($step->error(), true);
            $this->errors++;
        }

        $continue = ($result !== Step::ERROR) || !($step instanceof BlockingStep);
        if (!$continue) {
            $this->finalMessage();
        }

        return $continue;
    }

    /**
     * @param Step $step
     * @param string $prefix
     * @param int $result
     * @return bool
     */
    private function runStepScripts(
        Step $step,
        string $prefix,
        int $result = Step::NONE
    ): bool {

        $name = $step->name();
        $scriptLabel = sprintf("'%s' scripts for '%s' step", rtrim($prefix, '-'), $name);
        $scriptName = $this->findScriptName($name, $prefix);
        $allStepScripts = $this->scripts[$scriptName] ?? null;
        if (!$allStepScripts) {
            return true;
        }

        $validStepScripts = array_filter($allStepScripts, 'is_callable');

        $invalidScriptsCount = count($allStepScripts) - count($validStepScripts);
        if ($invalidScriptsCount) {
            $message = $invalidScriptsCount > 1
                ? "Found {$invalidScriptsCount} invalid script callbacks for {$scriptLabel}, they"
                : "Found one invalid script callback for {$scriptLabel}, it";
            $this->io->writeErrorIfVerbose("{$message} will be ignored.");
        }

        if (!$validStepScripts) {
            return true;
        }

        $this->io->writeIfVerbose("Start running {$scriptLabel}...");

        $continue = true;
        $exit = true;
        while ($continue && $validStepScripts) {
            $script = array_shift($validStepScripts);
            [$stepHalted, $propagationStopped] = $this->handleScriptSignal(
                $this->runStepScript($script, $scriptLabel, $step, $result),
                $name,
                $prefix,
                $exit
            );
            if ($propagationStopped) {
                $continue = false;
            }
            if ($stepHalted) {
                $exit = false;
            }
        }

        return $exit;
    }

    /**
     * @param ScriptHaltSignal|null $signal
     * @param string $name
     * @param string $prefix
     * @param bool $exit
     * @return array{bool, bool}
     */
    private function handleScriptSignal(
        ?ScriptHaltSignal $signal,
        string $name,
        string $prefix,
        bool $exit
    ): array {

        if (!$signal) {
            return [false, false];
        }

        $stepHalted = ($prefix === 'pre-') && $exit && $signal->isStepHalted();
        $propagationStopped = $signal->isPropagationStopped();
        $what = $stepHalted ? 'halted step execution' : 'stopped scripts propagation';
        ($stepHalted && $propagationStopped) and $what .= ' and stopped scripts propagation';
        $message = "A '{$prefix}' script for '{$name}' step {$what}";
        $reason = $signal->reason();
        $message .= $reason ? ": {$reason}" : '.';
        $this->io->writeComment($message);

        return [$stepHalted, $propagationStopped];
    }

    /**
     * @param string $name
     * @param string $prefix
     * @return string
     */
    private function findScriptName(string $name, string $prefix): string
    {
        if (!$this->runningSteps) {
            return $prefix . $name;
        }

        foreach (array_keys($this->scripts ?? []) as $scriptName) {
            if (strpos($scriptName, $prefix) !== 0) {
                continue;
            }
            $noPrefix = substr($scriptName, strlen($prefix));
            $canonicalStepName = SelectedStepsFactory::findStepNameByAlias($noPrefix, [$name]);
            if (!$canonicalStepName) {
                continue;
            }
            $canonicalScript = $prefix . $canonicalStepName;
            if ($scriptName !== $canonicalScript) {
                $this->io->writeComment(
                    "Script name '{$scriptName}' is deprecated, please use '{$canonicalScript}'."
                );
            }

            return $scriptName;
        }

        return $prefix . $name;
    }

    /**
     * @param callable $script
     * @param string $label
     * @param Step $step
     * @param int $result
     * @return ScriptHaltSignal|null
     */
    private function runStepScript(
        callable $script,
        string $label,
        Step $step,
        int $result
    ): ?ScriptHaltSignal {

        try {
            $return = $script($result, $step, $this->locator, $this->composer);
            if ($return instanceof ScriptHaltSignal) {
                return $return;
            }

            return null;
        } catch (\Throwable $error) {
            $this->io->writeErrorBlock("Error running {$label}:", $error->getMessage());

            return null;
        }
    }

    /**
     * @param string $message
     * @param bool $error
     * @return void
     */
    private function printMessages(string $message, bool $error = false): void
    {
        $error ? $this->io->writeErrorBlock($message) : $this->io->writeSuccess($message);
    }

    /**
     * @return int
     */
    private function finalMessage(): int
    {
        if ($this->isCommandMode) {
            return $this->errors > 0 ? self::ERROR : self::SUCCESS;
        }

        usleep(250000);

        if ($this->errors > 0) {
            $this->io->writeErrorBlock($this->error());

            return self::ERROR;
        }

        $this->io->writeSuccessBlock($this->success());

        return self::SUCCESS;
    }
}
