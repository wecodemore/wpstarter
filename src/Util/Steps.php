<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Step;

/**
 * A step whose routine consists in running other steps routines.
 */
final class Steps implements Step\PostProcessStep
{
    /**
     * @var Locator
     */
    private $locator;

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
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->locator = $locator;
        $this->steps = new \SplObjectStorage();
        $this->postProcessSteps = new \SplObjectStorage();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'wpstarter';
    }

    /**
     * @param Step\Step $step
     * @return Steps
     */
    public function addStep(Step\Step $step): Steps
    {
        $this->steps->attach($step);

        return $this;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        if (is_file($paths->wpParent('/wp-config.php'))) {
            return $config[Config::PREVENT_OVERWRITE]->not(OverwriteHelper::HARD);
        }

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
        $this->steps->rewind();
        $io = $this->locator->io();

        $scripts = $config[Config::SCRIPTS]->unwrapOrFallback([]);
        $this->runStepScripts($this, $io, $paths, $scripts, 'pre-', Step\Step::NONE);

        while ($this->steps->valid()) {

            /** @var \WeCodeMore\WpStarter\Step\Step $step */
            $step = $this->steps->current();
            $result = 0;
            $shouldProcess = $this->shouldProcess($step, $paths);

            $io->writeIfVerbose('Initiliazing "' . $step->name() . '" step.');
            $shouldProcess or $io->writeIfVerbose('Step "' . $step->name() . '" skipped.');

            if ($shouldProcess) {
                $this->runStepScripts($step, $io, $paths, $scripts, 'pre-', Step\Step::NONE);
                $result = $step->run($config, $paths);
            }

            if (!$this->handleResult($step, $io, $result)) {
                $this->runStepScripts($step, $io, $paths, $scripts, 'pre-', Step\Step::ERROR);

                return $this->finalMessage($io);
            }

            if ($step instanceof Step\PostProcessStep) {
                $this->postProcessSteps->attach($step);
            }

            if ($shouldProcess) {
                $this->runStepScripts($step, $io, $paths, $scripts, 'pre-', $result);
            }

            $this->steps->next();
        }

        $this->postProcess($io);

        $this->runStepScripts($this, $io, $paths, $scripts, 'pre-', Step\Step::SUCCESS);

        return $this->finalMessage($io);
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return $this->errors === 1
            ? 'An error occurred during WP Starter install, site might be not configured properly.'
            : 'Some errors occurred during WP Starter install, site is not configured properly.';
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
                'Remember that to make your site fully functional',
                'you either need to have an .env file with at least DB settings',
                'or set them in environment variables in some other way (e.g. via webserver).',
            ];

            $io->block($lines, 'yellow', false);
        }
    }

    /**
     * @param  \WeCodeMore\WpStarter\Step\Step $step
     * @param  Paths $paths
     * @return bool
     */
    private function shouldProcess(Step\Step $step, Paths $paths): bool
    {
        $comment = '';
        $process = $step->allowed($this->locator->config(), $paths);

        if ($process && $step instanceof Step\FileCreationStepInterface) {
            /** @var \WeCodeMore\WpStarter\Step\FileCreationStepInterface $step */
            $path = $step->targetPath($paths);
            $process = $this->locator->overwriteHelper()->shouldOverwite($path);
            $comment = $process ? '' : '- ' . basename($path) . ' exists and will be preserved.';
        }

        if ($process && $step instanceof Step\OptionalStep) {
            /** @var \WeCodeMore\WpStarter\Step\OptionalStep $step */
            $process = $step->askConfirm($this->locator->config(), $this->locator->io());
            $comment = $process ? '' : $step->skipped();
        }

        $process or $this->locator->io()->comment($comment);

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
    private function handleResult(Step\Step $step, Io $io, int $result): bool
    {
        if (($result & Step\Step::SUCCESS) === Step\Step::SUCCESS) {
            $this->printMessages($io, $step->success(), false);
        }

        if (($result & Step\Step::ERROR) === Step\Step::ERROR) {
            $this->printMessages($io, $step->error(), true);
            $this->errors++;
            if ($step instanceof Step\BlockingStep) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Step\Step $step
     * @param Io $io
     * @param Paths $paths
     * @param array $scripts
     * @param string $prefix
     * @param int $result
     */
    private function runStepScripts(
        Step\Step $step,
        Io $io,
        Paths $paths,
        array $scripts,
        string $prefix,
        int $result
    ) {

        $scriptsKey = $prefix . $step->name();

        $toRun = array_key_exists($scriptsKey, $scripts) ? (array)$scripts[$scriptsKey] : [];

        if ($toRun) {
            $io->writeIfVerbose("Running '{$scriptsKey}' scripts...");

            array_walk(
                $toRun,
                function (callable $script) use ($paths, $result) {
                    $script($this->locator, $result);
                }
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
            $error ? $io->error($line) : $io->ok($line);
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
        if ($this->errors > 0) {
            $io->block([$this->error()], 'red', true);

            return self::ERROR;
        }

        $io->block([$this->success()], 'green', false);

        return self::SUCCESS;
    }
}
