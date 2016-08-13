<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup\Steps;

use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\IO;

/**
 * Optional steps, depending on settings this step can be skipped based on user interaction.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
interface OptionalStepInterface extends StepInterface
{
    /**
     * Ask a corfirmation  and return result.
     *
     * To actually display the question on screen, use `$io->confirm()`.
     *
     * @see \WCM\WPStarter\Setup\IO::confirm()
     *
     * @param  \WCM\WPStarter\Setup\Config $config
     * @param  \WCM\WPStarter\Setup\IO $io
     * @return bool
     */
    public function askConfirm(Config $config, IO $io);

    /**
     * The message that should be printed when users says don't want to process this step.
     *
     * @return string
     */
    public function skipped();
}
