<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Utils\Config;
use WeCodeMore\WpStarter\Utils\IO;

/**
 * Optional steps, depending on settings this step can be skipped based on user interaction.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
interface OptionalStepInterface extends StepInterface
{
    /**
     * Ask a corfirmation  and return result.
     *
     * To actually display the question on screen, use `$io->confirm()`.
     *
     * @see \WeCodeMore\WpStarter\Utils\IO::confirm()
     *
     * @param  \WeCodeMore\WpStarter\Utils\Config $config
     * @param  \WeCodeMore\WpStarter\Utils\IO $io
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
