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
use WeCodeMore\WpStarter\Utils\Paths;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WeCodeMore\WpStarter
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
     * @param  \WeCodeMore\WpStarter\Utils\Config $config
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
     * @param int $verbosity
     * @return int
     */
    public function run(Paths $paths, $verbosity)
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
