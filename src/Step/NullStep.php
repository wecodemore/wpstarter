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
 * Implements the interface doing nothing.
 */
class NullStep implements Step
{
    /**
     * @return string
     */
    public function name(): string
    {
        return '';
    }

    /**
     * @param  \WeCodeMore\WpStarter\Config\Config $config
     * @param  Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        return false;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        return self::NONE;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return '';
    }
}
