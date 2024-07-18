<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Env;

abstract class Helpers
{
    /** @var WordPressEnvBridge|null */
    private static $bridge = null;

    /** @var string|null */
    private static $type = null;

    /** @var bool */
    private static $cacheEnabled = false;

    /** @var bool|null */
    private static $shouldCache = null;

    /** @var bool */
    private static bool $usePutenv = false;

    /**
     * @return void
     */
    final public static function enableCache(): void
    {
        self::$cacheEnabled = true;
    }

    /**
     * @return void
     */
    final public static function usePutenv(): void
    {
        self::$usePutenv = true;
    }

    /**
     * @param string $key
     * @return mixed
     */
    final public static function readVar(string $key)
    {
        if ($key === '') {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s(): Argument #1 ($key) must be a non-empty string.',
                    __METHOD__
                )
            );
        }

        return static::envBridge()->read($key);
    }

    /**
     * @param bool $reset
     * @return string
     */
    final public static function envType(bool $reset = false): string
    {
        if (!isset(static::$type) || $reset) {
            static::$type = static::envBridge()->determineEnvType();
        }

        return static::$type;
    }

    /**
     * @param string $fileName
     * @param string $path
     * @return array{string, bool}
     */
    final public static function loadEnvFiles(string $fileName, string $path): array
    {
        $loader = static::envBridge();

        if (!$loader->hasCachedValues()) {
            $loader->load($fileName, $path);
            $envType = static::envType(true);
            if ($envType !== 'example') {
                $loader->load("{$fileName}.{$envType}", $path);
            }

            $loader->setupConstants();

            return [$envType, false];
        }

        $loader->setupEnvConstants();

        return [static::envType(true), true];
    }

    /**
     * @return void
     */
    final public static function dumpEnvCache(): void
    {
        if (!defined('WPSTARTER_ENV_PATH')) {
            return;
        }
        if (static::shouldCacheEnv()) {
            $path = WPSTARTER_ENV_PATH . WordPressEnvBridge::CACHE_DUMP_FILE;
            static::envBridge()->dumpCached($path);
        }
    }

    /**
     * @return bool
     */
    final public static function shouldCacheEnv(): bool
    {
        if (!self::$cacheEnabled || !static::envBridge()->isWpSetup()) {
            return false;
        }

        if (!isset(static::$shouldCache)) {
            $env = static::envType();
            $skip = ($env === 'local') || (defined('WP_DEVELOPMENT_MODE') && WP_DEVELOPMENT_MODE);
            // Do not statically cache before plugins get a chance to change via filter
            if (!did_action('plugins_loaded')) {
                return !$skip;
            }
            static::$shouldCache = !apply_filters('wpstarter.skip-cache-env', $skip, $env);
        }

        return static::$shouldCache;
    }

    /**
     * @return bool
     */
    final public static function isEnvCacheEnabled(): bool
    {
        return static::$cacheEnabled;
    }

    /**
     * @return WordPressEnvBridge
     */
    private static function envBridge(): WordPressEnvBridge
    {
        if (!static::$bridge) {
            if (!defined('WPSTARTER_ENV_PATH') || !self::$cacheEnabled) {
                static::$bridge = new WordPressEnvBridge();
                static::$usePutenv and static::$bridge->usePutEnv();

                return static::$bridge;
            }

            $envCacheFile = WPSTARTER_ENV_PATH . WordPressEnvBridge::CACHE_DUMP_FILE;
            static::$bridge = WordPressEnvBridge::buildFromCacheDump($envCacheFile);
            static::$usePutenv and static::$bridge->usePutEnv();
        }

        return static::$bridge;
    }
}
