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

use WeCodeMore\WpStarter\Utils\IO;

/**
 * Steps that run a routine after all steps have been processed.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
interface PostProcessStepInterface extends StepInterface
{
    /**
     * Runs after all steps have been processed. Useful to print some messages or do some cleanup.
     *
     * @param \WeCodeMore\WpStarter\Utils\IO $io
     */
    public function postProcess(IO $io);
}
