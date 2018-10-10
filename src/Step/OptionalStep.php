<?php
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Io;

/**
 * Optional steps, depending on settings this step can be skipped based on user interaction.
 */
interface OptionalStep extends Step
{
    const ASK = 'ask';

    /**
     * Ask a corfirmation  and return result.
     *
     * To actually display the question on screen, use `$io->confirm()`.
     *
     * @see \WeCodeMore\WpStarter\Util\Io::askConfirm()
     *
     * @param  \WeCodeMore\WpStarter\Config\Config $config
     * @param  \WeCodeMore\WpStarter\Util\Io $io
     * @return bool
     */
    public function askConfirm(Config $config, Io $io): bool;

    /**
     * The message that should be printed when users say they want to skip this step.
     *
     * @return string
     */
    public function skipped(): string;
}
