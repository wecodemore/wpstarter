<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Utils;

use WeCodeMore\WpStarter\Step;

/**
 * A step whose work routine consists in running other steps routines.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class Stepper implements StepperInterface, Step\PostProcessStepInterface
{

    /**
     * @var \WeCodeMore\WpStarter\Utils\IO
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Utils\OverwriteHelper
     */
    private $overwrite;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Config
     */
    private $config;

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
     * @param \WeCodeMore\WpStarter\Utils\IO $io
     * @param \WeCodeMore\WpStarter\Utils\OverwriteHelper $overwrite
     */
    public function __construct(IO $io, OverwriteHelper $overwrite)
    {
        $this->io = $io;
        $this->overwrite = $overwrite;
        $this->steps = new \SplObjectStorage();
        $this->postProcessSteps = new \SplObjectStorage();
    }

    public function name()
    {
        return 'wpstarter';
    }

    /**
     * @inheritdoc
     */
    public function addStep(Step\StepInterface $step)
    {
        $this->steps->attach($step);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, Paths $paths)
    {
        $this->config = $config;
        $wp_config = $paths['wp-parent'] . '/wp-config.php';

        return $config['prevent-overwrite'] !== 'hard' || !is_file($wp_config);
    }

    /**
     * Process all added steps.
     *
     * @param Paths $paths
     * @param int $verbosity
     * @return int
     */
    public function run(Paths $paths, $verbosity)
    {
        $this->steps->rewind();

        $scripts = $this->config['scripts'] ?: [];

        $this->runStepScripts($this, $scripts, ['pre-', $paths, Step\StepInterface::NONE]);

        while ($this->steps->valid()) {

            /** @var \WeCodeMore\WpStarter\Step\StepInterface $step */
            $step = $this->steps->current();
            $result = 0;
            $shouldProcess = $this->shouldProcess($step, $paths);

            $this->io->writeVerbose('Initiliazing "' . $step->name() . '" step.');
            $shouldProcess or $this->io->writeVerbose('Step "' . $step->name() . '" skipped.');

            if ($shouldProcess) {
                $stepScriptData = ['pre-', $paths, Step\StepInterface::NONE];
                $this->runStepScripts($step, $scripts, $stepScriptData);
                $result = $step->run($paths, $verbosity);
            }

            if (!$this->handleResult($step, $result)) {

                $stepScriptData = ['post-', $paths, Step\StepInterface::ERROR];
                $this->runStepScripts($this, $scripts, $stepScriptData);

                return $this->finalMessage();
            }

            if ($step instanceof Step\PostProcessStepInterface) {
                $this->postProcessSteps->attach($step);
            }

            if ($shouldProcess) {
                $stepScriptData = ['post-', $paths, $result];
                $this->runStepScripts($step, $scripts, $stepScriptData);
            }

            $this->steps->next();
        }

        $this->postProcess($this->io);

        $this->runStepScripts($this, $scripts, ['post-', $paths, Step\StepInterface::SUCCESS]);

        return $this->finalMessage();
    }

    /**
     * The error message lines.
     *
     * @return array
     */
    public function error()
    {
        return $this->errors === 1
            ? [
                'An error occurred during WP Starter install,',
                'site might be not configured properly.',
            ]
            : [
                'Some errors occurred during WP Starter install,',
                'site is not configured properly.',
            ];
    }

    /**
     * The success message lines.
     *
     * @return array
     */
    public function success()
    {
        return ['WP Starter finished successfully!'];
    }

    /**
     * Runs after all steps have been processed bay calling post process method on all steps.
     *
     * @param \WeCodeMore\WpStarter\Utils\IO $io
     */
    public function postProcess(IO $io)
    {
        $this->postProcessSteps->rewind();
        while ($this->postProcessSteps->valid()) {
            /** @var \WeCodeMore\WpStarter\Step\PostProcessStepInterface $step */
            $step = $this->postProcessSteps->current();
            $io->writeVerbose('Running post-processing for step "' . $step->name() . '"...');
            $step->postProcess($io);
            $this->postProcessSteps->next();
        }
    }

    /**
     * @param  \WeCodeMore\WpStarter\Step\StepInterface $step
     * @param  Paths $paths
     * @return bool
     */
    private function shouldProcess(Step\StepInterface $step, Paths $paths)
    {
        $comment = '';
        $process = $step->allowed($this->config, $paths);
        if ($process && $step instanceof Step\FileCreationStepInterface) {
            /** @var \WeCodeMore\WpStarter\Step\FileCreationStepInterface $step */
            $path = $step->targetPath($paths);
            $process = $this->overwrite->should($path);
            $comment = $process ? '' : '- ' . basename($path) . ' exists and will be preserved.';
        }
        if ($process && $step instanceof Step\OptionalStepInterface) {
            /** @var \WeCodeMore\WpStarter\Step\OptionalStepInterface $step */
            $process = $step->askConfirm($this->config, $this->io);
            $comment = $process ? '' : $step->skipped();
        }
        $process or $this->io->comment($comment);

        return $process;
    }

    /**
     * Show success or error message according to step process result.
     * Increment error count in case of error.
     * Return false in case of error for blocking steps.
     *
     * @param  \WeCodeMore\WpStarter\Step\StepInterface $step
     * @param  int $result
     * @return bool
     */
    private function handleResult(Step\StepInterface $step, $result)
    {
        if ($result & Step\StepInterface::SUCCESS) {
            $this->printMessages($step->success(), false);
        }

        if ($result & Step\StepInterface::ERROR) {
            $this->printMessages($step->error(), true);
            $this->errors++;
            if ($step instanceof Step\BlockingStepInterface) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \WeCodeMore\WpStarter\Step\StepInterface $step
     * @param array $scripts
     * @param array $data
     */
    private function runStepScripts(Step\StepInterface $step, array $scripts, array $data)
    {
        list($type, $paths, $result) = $data;
        $scriptsKey = $type . $step->name();

        $toRun = array_key_exists($scriptsKey, $scripts) ? (array)$scripts[$scriptsKey] : [];

        if ($toRun) {
            $this->io->writeVerbose("Running '{$scriptsKey}' scripts...");

            array_walk($toRun, function (callable $script) use ($paths, $result) {
                $script($this->config, $paths, $this->io, $result);
            });
        }
    }

    /**
     * Print messages to console line by line.
     *
     * @param string $message
     * @param bool $error
     */
    private function printMessages($message, $error = false)
    {
        $messages = explode(PHP_EOL, $message);
        foreach ($messages as $line) {
            $error ? $this->io->error($line) : $this->io->ok($line);
        }
    }

    /**
     * Print to console final message.
     *
     * @return int
     */
    private function finalMessage()
    {
        if ($this->errors > 0) {
            $this->io->block($this->error(), 'red', true);

            return self::ERROR;
        }

        $this->io->block($this->success(), 'green', false);

        return self::SUCCESS;
    }
}
