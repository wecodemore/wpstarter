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
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Clean environment cache file.
 */
final class FlushEnvCacheStep implements ConditionalStep
{
    public const NAME = 'flushenvcache';

    /**
     * @var \Composer\Util\Filesystem
     */
    private $filesystem;

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->filesystem = $locator->composerFilesystem();
    }

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
        /** @var string $envDir */
        $envDir = $config[Config::ENV_DIR]->unwrapOrFallback($paths->root());
        $envCacheFile = "{$envDir}/" . WordPressEnvBridge::CACHE_DUMP_FILE;

        return file_exists($envCacheFile);
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        /** @var string $envDir */
        $envDir = $config[Config::ENV_DIR]->unwrapOrFallback($paths->root());
        $envCacheFile = "{$envDir}/" . WordPressEnvBridge::CACHE_DUMP_FILE;

        return $this->filesystem->remove($envCacheFile) ? self::SUCCESS : self::ERROR;
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

    /**
     * @return string
     */
    public function conditionsNotMet(): string
    {
        return 'environment cache file not found';
    }
}
