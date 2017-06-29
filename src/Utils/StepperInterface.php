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

use WeCodeMore\WpStarter\Step\StepInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
interface StepperInterface extends StepInterface
{
    /**
     * Add a step to be processed.
     *
     * @param  \WeCodeMore\WpStarter\Step\StepInterface $step
     * @return \WeCodeMore\WpStarter\Utils\StepperInterface    Itself for fluent interface
     */
    public function addStep(StepInterface $step);
}
