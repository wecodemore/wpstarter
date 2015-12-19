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

use WCM\WPStarter\Setup\Steps\BlockingStepInterface;
use WCM\WPStarter\Setup\Steps\FileStepInterface;
use WCM\WPStarter\Setup\Steps\OptionalStepInterface;
use WCM\WPStarter\Setup\Steps\PostProcessStepInterface;
use WCM\WPStarter\Setup\Steps\StepInterface;
use ArrayAccess;
use SplObjectStorage;

/**
 * A step whose work routine consists in running other steps routines.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class Stepper implements StepperInterface, PostProcessStepInterface
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
     * @param \WCM\WPStarter\Setup\IO              $io
     * @param \WCM\WPStarter\Setup\OverwriteHelper $overwrite
     */
    public function __construct(IO $io, OverwriteHelper $overwrite)
    {
        $this->io = $io;
        $this->overwrite = $overwrite;
        $this->steps = new SplObjectStorage();
        $this->postProcessSteps = new SplObjectStorage();
    }

    /**
     * @inheritdoc
     */
    public function addStep(StepInterface $step)
    {
        $this->steps->attach($step);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, ArrayAccess $paths)
    {
        $this->config = $config;
        $wp_config = $paths['root'].'/wp-config.php';

        return $config['prevent-overwrite'] !== 'hard' || ! is_file($wp_config);
    }

    /**
     * Process all added steps.
     *
     * @param  \ArrayAccess $paths
     * @return int
     */
    public function run(ArrayAccess $paths)
    {
        $this->io->comment('WP Starter is going to start installation...');
        $this->steps->rewind();
        while ($this->steps->valid()) {
            /** @var \WCM\WPStarter\Setup\Steps\StepInterface $step */
            $step = $this->steps->current();
            /** @var int $result */
            $result = $this->shouldProcess($step, $paths) ? $step->run($paths) : 0;
            if (! $this->handleResult($step, $result)) {
                return $this->finalMessage();
            }
            $step instanceof PostProcessStepInterface and $this->postProcessSteps->attach($step);
            $this->steps->next();
        }
        $this->postProcess($this->io);

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
            ? array(
                'An error occurred during WP Starter install,',
                'site might be not configured properly.',
            )
            : array(
                'Some errors occurred during WP Starter install,',
                'site is not configured properly.',
            );
    }

    /**
     * The success message lines.
     *
     * @return array
     */
    public function success()
    {
        return array('WP Starter finished successfully!');
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
     * @param  \ArrayAccess                             $paths
     * @return bool
     */
    private function shouldProcess(StepInterface $step, ArrayAccess $paths)
    {
        $comment = '';
        $process = $step->allowed($this->config, $paths);
        if ($process && $step instanceof FileStepInterface) {
            /** @var \WCM\WPStarter\Setup\Steps\FileStepInterface $step */
            $path = $step->targetPath($paths);
            $process = $this->overwrite->should($path);
            $comment = $process ? '' : '- '.basename($path).' exists and will be preserved.';
        }
        if ($process && $step instanceof OptionalStepInterface) {
            /** @var \WCM\WPStarter\Setup\Steps\OptionalStepInterface $step */
            $process = $step->question($this->config, $this->io);
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
     * @param  int                                      $result
     * @return bool
     */
    private function handleResult(StepInterface $step, $result)
    {
        if ($result & StepInterface::SUCCESS) {
            $this->printMessages($step->success(), false);
        }
        if ($result & StepInterface::ERROR) {
            $this->printMessages($step->error(), true);
            $this->errors++;
            if ($step instanceof BlockingStepInterface) {
                return false;
            }
        }

        return true;
    }

    /**
     * Print messages to console line by line.
     *
     * @param string $message
     * @param bool   $error
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
