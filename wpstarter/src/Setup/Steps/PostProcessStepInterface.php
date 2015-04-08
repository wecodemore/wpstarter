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

use WCM\WPStarter\Setup\IO;

/**
 * Steps that run a routine after all steps have been processed.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
interface PostProcessStepInterface
{
    /**
     * Runs after all steps have been processed. Useful to print some messages or do some cleanup.
     *
     * @param \WCM\WPStarter\Setup\IO $io
     */
    public function postProcess(IO $io);
}
