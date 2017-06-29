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
 * A "working unit" for WP Starter. Steps are processed one-by-one and any step performs a tasks.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
interface StepInterface
{
    const ERROR = 1;
    const SUCCESS = 2;
    const NONE = 4;

    /**
     * Return an unique name for the step.
     *
     * @return string
     */
    public function name();

    /**
     * Return true if the step is allowed, i.e. the run method have to be called or not
     *
     * @param  \WeCodeMore\WpStarter\Utils\Config $config
     * @param  Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths);

    /**
     * Process the step.
     *
     * @param  Paths $paths Have to return one of the step constants.
     * @param int $verbosity
     * @return int
     */
    public function run(Paths $paths, $verbosity);

    /**
     * Return error message if any.
     *
     * @return string
     */
    public function error();

    /**
     * Return success message if any.
     *
     * @return string
     */
    public function success();
}
