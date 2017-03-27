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
use WCM\WPStarter\Setup\Paths;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WPStarter
 * @license http://opensource.org/licenses/MIT MIT
 */
final class NullStep implements StepInterface
{

    /**
     * Implements the interface doing nothing.
     *
     * @return string
     */
    public function name()
    {
        return '';
    }

    /**
     * Implements the interface doing nothing.
     *
     * @param  \WCM\WPStarter\Setup\Config $config
     * @param  Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths)
    {
        return false;
    }

    /**
     * Implements the interface doing nothing.
     *
     * @param  Paths $paths Have to return one of the step constants.
     * @return int
     */
    public function run(Paths $paths)
    {
        return self::NONE;
    }

    /**
     * Implements the interface doing nothing.
     *
     * @return string
     */
    public function error()
    {
        return '';
    }

    /**
     * Implements the interface doing nothing.
     *
     * @return string
     */
    public function success()
    {
        return '';
    }
}
