<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * A "working unit" for WP Starter. Steps are processed one-by-one and any step performs a tasks.
 */
interface Step
{
    public const ERROR = 1;
    public const SUCCESS = 2;
    public const NONE = 4;

    /**
     * Return a unique name for the step.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Return true if the step is allowed, i.e. the run method have to be called or not
     *
     * @param  \WeCodeMore\WpStarter\Config\Config $config
     * @param  Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool;

    /**
     * Process the step.
     *
     * @param Config $config
     * @param  Paths $paths Have to return one of the step constants.
     * @return int
     */
    public function run(Config $config, Paths $paths): int;

    /**
     * Return error message if any.
     *
     * @return string
     */
    public function error(): string;

    /**
     * Return success message if any.
     *
     * @return string
     */
    public function success(): string;
}
