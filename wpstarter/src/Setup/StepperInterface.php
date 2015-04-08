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

use WCM\WPStarter\Setup\Steps\StepInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
interface StepperInterface extends StepInterface
{
    /**
     * Add a step to be processed.
     *
     * @param  \WCM\WPStarter\Setup\Steps\StepInterface $step
     * @return \WCM\WPStarter\Setup\StepperInterface    Itself for fluent interface
     */
    public function addStep(StepInterface $step);
}
