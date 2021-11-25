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
use WeCodeMore\WpStarter\Env\WordPressEnvBridge;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Clean environment cache file.
 */
final class FlushEnvCacheStep implements Step
{
    public const NAME = 'flush-env-cache';

    /**
     * @return string
     */
    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @param  \WeCodeMore\WpStarter\Config\Config $config
     * @param  Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        return file_exists($paths->wpParent(WordPressEnvBridge::CACHE_DUMP_FILE));
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $cachedEnv = $paths->wpParent(WordPressEnvBridge::CACHE_DUMP_FILE);

        if (!is_file($cachedEnv) || !is_readable($cachedEnv)) {
            return self::ERROR;
        }

        @unlink($cachedEnv);

        if (!file_exists($cachedEnv)) {
            return self::SUCCESS;
        }

        return self::ERROR;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return 'Failed to clean environment cache.';
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return '<comment>Environment cache</comment> cleaned successfully.';
    }
}
