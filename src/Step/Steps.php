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

/**
 * A step whose routine consists in running other steps routines.
 *
 * This is used as main "task runner" for WP Starter.
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
     * @var array<string, array>
     */
    private $scripts;

    /**
     * @var \SplObjectStorage<Step,null>
     */
    private $steps;

    /**
     * @var \SplObjectStorage<PostProcessStep,null>|null
     */
    private $postProcessSteps;

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
    private $isCommandMode;

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
        $this->isCommandMode = $isCommandMode;
        $this->steps = new \SplObjectStorage();
        $this->scripts = $this->locator->config()[Config::SCRIPTS]->unwrap();
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

        $io = $this->locator->io();

        $this->running = true;

        $this->runningScripts = 'pre';
        $this->runStepScripts($this, $io, 'pre-');
        $this->runningScripts = '';

        // We check here because "pre" scripts can add or remove steps.
        if (!$this->count()) {
            return Step::NONE;
        }

        $this->steps->rewind();
        while ($this->steps->valid()) {
            $step = $this->steps->current();

            if (!$this->runStep($step, $config, $io, $paths)) {
                return Step::ERROR;
            }

            $this->steps->next();
        }

        $this->postProcess($io);

        $this->runningScripts = 'post';
        $this->runStepScripts($this, $io, 'post-', Step::SUCCESS);
        $this->runningScripts = '';

        $this->running = false;

        return $this->finalMessage($io);
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
    public function postProcess(Io $io)
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
     * @param Io $io
     * @param Paths $paths
     * @return bool
     */
    private function runStep(Step $step, Config $config, Io $io, Paths $paths): bool
    {
        $name = $step->name();
        if (!$name) {
            return true;
        }

        $io->writeIfVerbose("- Initializing '{$name}' step.");

        if ($step instanceof PostProcessStep) {
            $this->postProcessSteps or $this->postProcessSteps = new \SplObjectStorage();
            $this->postProcessSteps->attach($step);
        }

        if (!$this->shouldProcess($step, $paths, $io)) {
            return true;
        }

        $this->runStepScripts($step, $io, 'pre-');

        try {
            $result = $step->run($config, $paths);
        } catch (\Throwable $throwable) {
            $this->printMessages($io, $throwable->getMessage(), true);
            $result = self::ERROR;
        }

        $this->runStepScripts($step, $io, 'post-', $result);

        return $this->continueOnStepResult($step, $result, $io);
    }

    /**
     * @param Step $step
     * @param Paths $paths
     * @param Io $io
     * @return bool
     */
    private function shouldProcess(Step $step, Paths $paths, Io $io): bool
    {
        $comment = '';
        $process = $step->allowed($this->locator->config(), $paths);

        if ($process && $step instanceof FileCreationStepInterface) {
            $path = $step->targetPath($paths);
            $process = $this->locator->overwriteHelper()->shouldOverwrite($path);
            $comment = $process ? '' : '- ' . basename($path) . ' exists and will be preserved.';
        }

        if ($process && $step instanceof OptionalStep) {
            $process = $step->askConfirm($this->locator->config(), $this->locator->io());
            $comment = $process ? '' : $step->skipped();
        }

        if (!$process) {
            $comment
                ? $io->writeComment($comment)
                : $io->writeIfVerbose(sprintf("- Step '%s' skipped: not allowed.", $step->name()));
        }

        return $process;
    }

    /**
     * @param Step $step
     * @param int $result
     * @param Io $io
     * @return bool
     */
    private function continueOnStepResult(Step $step, int $result, Io $io): bool
    {
        if ($result === Step::SUCCESS) {
            $this->printMessages($io, $step->success(), false);
        }

        if (($result & Step::ERROR) === Step::ERROR) {
            $this->printMessages($io, $step->error(), true);
            $this->errors++;
        }

        $continue = ($result !== Step::ERROR) || !($step instanceof BlockingStep);
        if (!$continue) {
            $this->finalMessage($io);
        }

        return $continue;
    }

    /**
     * @param Step $step
     * @param Io $io
     * @param string $prefix
     * @param int $result
     * @return void
     */
    private function runStepScripts(Step $step, Io $io, string $prefix, int $result = Step::NONE)
    {
        $name = $step->name();
        $scriptLabel = "'{$prefix}' scripts for '{$name}' step";

        $allStepScripts = $this->scripts[$prefix . $name] ?? null;
        if (!$allStepScripts) {
            return;
        }

        $validStepScripts = array_filter($allStepScripts, 'is_callable');

        $invalidScriptsCount = count($allStepScripts) - count($validStepScripts);
        if ($invalidScriptsCount) {
            $message = $invalidScriptsCount > 1
                ? "Found {$invalidScriptsCount} invalid script callbacks for {$scriptLabel}, they"
                : "Found one invalid script callback for {$scriptLabel}, it";
            $io->writeErrorIfVerbose("{$message} will be ignored.");
        }

        if (!$validStepScripts) {
            return;
        }

        $io->writeIfVerbose("Start running {$scriptLabel}...");

        foreach ($validStepScripts as $script) {
            $this->runStepScript($script, $scriptLabel, $step, $result, $io);
        }
    }

    /**
     * @param callable $script
     * @param string $label
     * @param Step $step
     * @param int $result
     * @param Io $io
     * @return void
     */
    private function runStepScript(callable $script, string $label, Step $step, int $result, Io $io)
    {
        try {
            $script($result, $step, $this->locator, $this->composer);
        } catch (\Throwable $error) {
            $io->writeErrorBlock("Error running {$label}:", $error->getMessage());
        }
    }

    /**
     * @param Io $io
     * @param string $message
     * @param bool $error
     * @return void
     */
    private function printMessages(Io $io, string $message, bool $error = false)
    {
        $error ? $io->writeErrorBlock($message) : $io->writeSuccess($message);
    }

    /**
     * @param Io $io
     * @return int
     */
    private function finalMessage(Io $io): int
    {
        if ($this->isCommandMode) {
            return $this->errors > 0 ? self::ERROR : self::SUCCESS;
        }

        usleep(250000);

        if ($this->errors > 0) {
            $io->writeErrorBlock($this->error());

            return self::ERROR;
        }

        $io->writeSuccessBlock($this->success());

        return self::SUCCESS;
    }
}
