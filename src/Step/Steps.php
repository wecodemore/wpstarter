<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use Composer\Composer;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Io;
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
     * @var \SplObjectStorage
     */
    private $steps;

    /**
     * @var \SplObjectStorage
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
     * @param bool $isCommandMode
     */
    public function __construct(Locator $locator, Composer $composer, bool $isCommandMode)
    {
        $this->locator = $locator;
        $this->composer = $composer;
        $this->steps = new \SplObjectStorage();
        $this->postProcessSteps = new \SplObjectStorage();
        $this->isCommandMode = $isCommandMode;
    }

    /**
     * @return int
     */
    public function count()
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
     * @param Step[] $steps
     * @return Steps
     */
    public function addStep(Step $step, Step ...$steps): Steps
    {
        if (!$this->running && !($this->isCommandMode && $this->runningScripts)) {
            $this->steps->attach($step);
            array_walk($steps, [$this->steps, 'attach']);
        }

        return $this;
    }

    /**
     * @param Step $step
     * @param Step[] $steps
     * @return Steps
     */
    public function removeStep(Step $step, Step ...$steps): Steps
    {
        if (!$this->running && !($this->isCommandMode && $this->runningScripts)) {
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
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        if ($this->running || $this->runningScripts) {
            return Step::NONE;
        }

        $this->steps->rewind();
        $io = $this->locator->io();

        $scripts = $config[Config::SCRIPTS]->unwrapOrFallback([]);

        $this->runningScripts = 'pre';
        $this->runStepScripts($this, $io, $scripts, 'pre-', Step::NONE);
        $this->runningScripts = '';

        $this->running = true;

        while ($this->steps->valid()) {

            /** @var \WeCodeMore\WpStarter\Step\Step $step */
            $step = $this->steps->current();

            if (!$this->runStep($step, $config, $io, $paths, $scripts)) {
                return Step::ERROR;
            }

            $this->steps->next();
        }

        $this->postProcess($io);

        $this->runningScripts = 'post';
        $this->runStepScripts($this, $io, $scripts, 'post-', Step::SUCCESS);
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
            ? 'One WP Starter step failed, the project might be not configured properly.'
            : 'Some WP Starter steps failed, the project might be not configured properly.';
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
     * @param \WeCodeMore\WpStarter\Util\Io $io
     */
    public function postProcess(Io $io)
    {
        if ($this->running || $this->runningScripts) {
            return;
        }

        $this->postProcessSteps->rewind();
        while ($this->postProcessSteps->valid()) {
            /** @var \WeCodeMore\WpStarter\Step\PostProcessStep $step */
            $step = $this->postProcessSteps->current();
            $io->writeIfVerbose('Running post-processing for step "' . $step->name() . '"...');
            $step->postProcess($io);
            $this->postProcessSteps->next();
        }

        $env = $this->locator->config()[Config::ENV_FILE]->unwrapOrFallback('.env');
        if (!is_file($this->locator->paths()->root($env))) {
            $lines = [
                'Remember that to make your site fully functional you either need to have an .env '
                . 'file with at least DB settings or set them in environment variables in some '
                . 'other way (e.g. via webserver).',
            ];

            $io->writeColorBlock('yellow', ...$lines);
        }
    }

    /**
     * @param Step $step
     * @param Config $config
     * @param Io $io
     * @param Paths $paths
     * @param array $scripts
     * @return bool
     */
    private function runStep(Step $step, Config $config, Io $io, Paths $paths, array $scripts): bool
    {
        $io->writeIfVerbose('Initializing "' . $step->name() . '" step.');
        $step instanceof PostProcessStep and $this->postProcessSteps->attach($step);

        if (!$this->shouldProcess($step, $paths)) {
            $io->writeIfVerbose('- step "' . $step->name() . '" skipped.');

            return true;
        }

        $this->runStepScripts($step, $io, $scripts, 'pre-', Step::NONE);

        try {
            $result = $step->run($config, $paths);
        } catch (\Throwable $throwable) {
            $this->printMessages($io, $throwable->getMessage(), true);
            $result = self::ERROR;
        }

        $this->runStepScripts($step, $io, $scripts, 'post-', Step::ERROR);

        if (!$this->continueOnResult($step, $io, $result)) {
            $this->finalMessage($io);

            return false;
        }

        return true;
    }

    /**
     * @param  \WeCodeMore\WpStarter\Step\Step $step
     * @param  Paths $paths
     * @return bool
     */
    private function shouldProcess(Step $step, Paths $paths): bool
    {
        $comment = '';
        $process = $step->allowed($this->locator->config(), $paths);

        if ($process && $step instanceof FileCreationStepInterface) {
            /** @var \WeCodeMore\WpStarter\Step\FileCreationStepInterface $step */
            $path = $step->targetPath($paths);
            $process = $this->locator->overwriteHelper()->shouldOverwrite($path);
            $comment = $process ? '' : '- ' . basename($path) . ' exists and will be preserved.';
        }

        if ($process && $step instanceof OptionalStep) {
            /** @var \WeCodeMore\WpStarter\Step\OptionalStep $step */
            $process = $step->askConfirm($this->locator->config(), $this->locator->io());
            $comment = $process ? '' : $step->skipped();
        }

        $process or $this->locator->io()->writeComment($comment);

        return $process;
    }

    /**
     * Show success or error message according to step process result.
     * Increment error count in case of error.
     * Return false in case of error for blocking steps.
     *
     * @param  \WeCodeMore\WpStarter\Step\Step $step
     * @param Io $io
     * @param  int $result
     * @return bool
     */
    private function continueOnResult(Step $step, Io $io, int $result): bool
    {
        if ($result === Step::SUCCESS) {
            $this->printMessages($io, $step->success(), false);
        }

        if (($result & Step::ERROR) === Step::ERROR) {
            $this->printMessages($io, $step->error(), true);
            $this->errors++;
            if ($step instanceof BlockingStep) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Step $step
     * @param Io $io
     * @param array $scripts
     * @param string $prefix
     * @param int $result
     */
    private function runStepScripts(
        Step $step,
        Io $io,
        array $scripts,
        string $prefix,
        int $result
    ) {

        $scriptsKey = $prefix . $step->name();

        $toRun = array_key_exists($scriptsKey, $scripts) ? (array)$scripts[$scriptsKey] : null;
        if (!$toRun) {
            return;
        }

        $runner = function (callable $script) use ($scriptsKey, $step, $result, $io) {
            $this->runStepScript($script, $scriptsKey, $step, $result, $io);
        };

        $io->writeIfVerbose("Running '{$scriptsKey}' scripts...");
        array_walk($toRun, $runner);
    }

    /**
     * @param callable $script
     * @param string $scriptsKey
     * @param Step $step
     * @param int $result
     * @param Io $io
     */
    private function runStepScript(
        callable $script,
        string $scriptsKey,
        Step $step,
        int $result,
        Io $io
    ) {

        try {
            $script($result, $step, $this->locator, $this->composer);
        } catch (\Throwable $error) {
            $io->writeErrorBlock(
                "Error running a script of event '{$scriptsKey}':",
                $error->getMessage()
            );
        }
    }

    /**
     * Print messages to console line by line.
     *
     * @param Io $io
     * @param string $message
     * @param bool $error
     */
    private function printMessages(Io $io, string $message, bool $error = false)
    {
        $messages = explode(PHP_EOL, $message);
        foreach ($messages as $line) {
            $error ? $io->writeError($line) : $io->writeSuccess($line);
        }
    }

    /**
     * Print to console final message.
     *
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
            $io->writeError($this->error());

            return self::ERROR;
        }

        $io->writeSuccessBlock($this->success());

        return self::SUCCESS;
    }
}
