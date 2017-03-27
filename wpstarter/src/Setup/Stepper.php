<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup;

/**
 * A step whose work routine consists in running other steps routines.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class Stepper implements StepperInterface, Steps\PostProcessStepInterface
{

    /**
     * @var \WCM\WPStarter\Setup\IO
     */
    private $io;

    /**
     * @var \WCM\WPStarter\Setup\OverwriteHelper
     */
    private $overwrite;

    /**
     * @var \WCM\WPStarter\Setup\Config
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
     * @param \WCM\WPStarter\Setup\IO $io
     * @param \WCM\WPStarter\Setup\OverwriteHelper $overwrite
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
    public function addStep(Steps\StepInterface $step)
    {
        $this->steps->attach($step);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, \ArrayAccess $paths)
    {
        $this->config = $config;
        $wp_config = $paths['wp-parent'] . '/wp-config.php';

        return $config['prevent-overwrite'] !== 'hard' || !is_file($wp_config);
    }

    /**
     * Process all added steps.
     *
     * @param  \ArrayAccess $paths
     * @return int
     */
    public function run(\ArrayAccess $paths)
    {
        $this->io->comment('WP Starter is going to start...');
        $this->steps->rewind();

        $scripts = $this->config['scripts'] ?: [];

        $this->runStepScripts($this, $scripts, ['pre-', $paths, Steps\StepInterface::NONE]);

        while ($this->steps->valid()) {

            /** @var \WCM\WPStarter\Setup\Steps\StepInterface $step */
            $step = $this->steps->current();
            $result = 0;
            $shouldProcess = $this->shouldProcess($step, $paths);

            if ($shouldProcess) {
                $stepScriptData = ['pre-', $paths, Steps\StepInterface::NONE];
                $this->runStepScripts($step, $scripts, $stepScriptData);
                $result = $step->run($paths);
            }

            if (!$this->handleResult($step, $result)) {

                $stepScriptData = ['post-', $paths, Steps\StepInterface::ERROR];
                $this->runStepScripts($this, $scripts, $stepScriptData);

                return $this->finalMessage();
            }

            if ($step instanceof Steps\PostProcessStepInterface) {
                $this->postProcessSteps->attach($step);
            }

            if ($shouldProcess) {
                $stepScriptData = ['post-', $paths, $result];
                $this->runStepScripts($step, $scripts, $stepScriptData);
            }

            $this->steps->next();
        }

        $this->postProcess($this->io);

        $this->runStepScripts($this, $scripts, ['post-', $paths, Steps\StepInterface::SUCCESS]);

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
     * @param \WCM\WPStarter\Setup\IO $io
     */
    public function postProcess(IO $io)
    {
        $this->postProcessSteps->rewind();
        while ($this->postProcessSteps->valid()) {
            /** @var \WCM\WPStarter\Setup\Steps\PostProcessStepInterface $step */
            $step = $this->postProcessSteps->current();
            $step->postProcess($io);
            $this->postProcessSteps->next();
        }
    }

    /**
     * @param  \WCM\WPStarter\Setup\Steps\StepInterface $step
     * @param  \ArrayAccess $paths
     * @return bool
     */
    private function shouldProcess(Steps\StepInterface $step, \ArrayAccess $paths)
    {
        $comment = '';
        $process = $step->allowed($this->config, $paths);
        if ($process && $step instanceof Steps\FileCreationStepInterface) {
            /** @var \WCM\WPStarter\Setup\Steps\FileCreationStepInterface $step */
            $path = $step->targetPath($paths);
            $process = $this->overwrite->should($path);
            $comment = $process ? '' : '- ' . basename($path) . ' exists and will be preserved.';
        }
        if ($process && $step instanceof Steps\OptionalStepInterface) {
            /** @var \WCM\WPStarter\Setup\Steps\OptionalStepInterface $step */
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
     * @param  \WCM\WPStarter\Setup\Steps\StepInterface $step
     * @param  int $result
     * @return bool
     */
    private function handleResult(Steps\StepInterface $step, $result)
    {
        if ($result & Steps\StepInterface::SUCCESS) {
            $this->printMessages($step->success(), false);
        }

        if ($result & Steps\StepInterface::ERROR) {
            $this->printMessages($step->error(), true);
            $this->errors++;
            if ($step instanceof Steps\BlockingStepInterface) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \WCM\WPStarter\Setup\Steps\StepInterface $step
     * @param array $scripts
     * @param array $data
     */
    private function runStepScripts(Steps\StepInterface $step, array $scripts, array $data)
    {
        list($type, $paths, $result) = $data;
        $scriptsKey = $type . $step->name();

        $toRun = array_key_exists($scriptsKey, $scripts) ? (array)$scripts[$scriptsKey] : [];

        $toRun and array_walk($toRun, function (callable $script) use ($paths, $result) {
            $script($this->config, $paths, $this->io, $result);
        });
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
