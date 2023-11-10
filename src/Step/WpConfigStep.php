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
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Step that generates and saves wp-config.php in webroot.
 *
 * This could be seen as the "main" WP Starter step, because it allows WordPress to work by creating
 * a wp-config.php file that includes all the necessary configuration.
 */
final class WpConfigStep implements FileCreationStep, BlockingStep
{
    public const NAME = 'wpconfig';

    /**
     * @var \WeCodeMore\WpStarter\Io\Io
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Util\FileContentBuilder
     */
    private $builder;

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var \WeCodeMore\WpStarter\Util\Salter
     */
    private $salter;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->io = $locator->io();
        $this->builder = $locator->fileContentBuilder();
        $this->filesystem = $locator->filesystem();
        $this->salter = $locator->salter();
        $this->config = $locator->config();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        return true;
    }

    /**
     * @param Paths $paths
     * @return string
     */
    public function targetPath(Paths $paths): string
    {
        /** @var string $dir */
        $dir = $this->config[Config::WP_CONFIG_PATH]->unwrap();

        return "{$dir}/wp-config.php";
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        /** @var string $from */
        $from = $this->config[Config::WP_CONFIG_PATH]->unwrap();
        $wpParent = $paths->wpParent();

        $autoload = $paths->vendor('autoload.php');

        $cacheEnv = $config[Config::CACHE_ENV]->unwrapOrFallback(true);

        /** @var string $earlyHookFile */
        $earlyHookFile = $config[Config::EARLY_HOOKS_FILE]->unwrapOrFallback('');
        if ($earlyHookFile) {
            $earlyHookFile = $this->relPath("{$from}/index.php", $earlyHookFile, false);
        }

        /** @var string $envBootstrapDir */
        $envBootstrapDir = $config[Config::ENV_BOOTSTRAP_DIR]->unwrapOrFallback('');
        if ($envBootstrapDir) {
            $envBootstrapDir = $this->relPath($from, $paths->root($envBootstrapDir));
        }

        /** @var string $envDirName */
        $envDirName = $config[Config::ENV_DIR]->unwrapOrFallback('');
        $envDir = $paths->root($envDirName);
        $this->filesystem->createDir($envDir);
        $envRelDir = $this->relPath($from, $envDir);
        $rootRelDir = $this->relPath($from, $paths->root());

        $register = $config[Config::REGISTER_THEME_FOLDER]->unwrapOrFallback(false);
        ($register === OptionalStep::ASK) and $register = $this->askForRegister();

        $contentRelDir = $this->relPath($from, $paths->wpContent());
        $contentRelUrlPath = $this->relPath($wpParent, $paths->wpContent());
        $wpRelDir = $this->relPath($from, $paths->wp());
        $wpRelUrlDir = $this->relPath($wpParent, $paths->wp());

        $target = $this->targetPath($paths);
        $wpConfigLoaderPath = $paths->wpParent('wp-config.php');
        $compatMode = $wpConfigLoaderPath === $target;

        $vars = [
            'AUTOLOAD_PATH' => $this->relPath("{$from}/index.php", $autoload, false),
            'CACHE_ENV' => $cacheEnv ? '1' : '',
            'EARLY_HOOKS_FILE' => $earlyHookFile,
            'ENV_BOOTSTRAP_DIR' => $envBootstrapDir ?: $envRelDir,
            'ENV_FILE_NAME' => $config[Config::ENV_FILE]->unwrapOrFallback('.env'),
            'ENV_USE_PUTENV' => $config[Config::ENV_USE_PUTENV]->unwrapOrFallback(false),
            'WPSTARTER_PATH' => $compatMode ? $envRelDir : $rootRelDir,
            'ENV_REL_PATH' => $envRelDir,
            'REGISTER_THEME_DIR' => $register ? 'true' : 'false',
            'WP_CONTENT_PATH' => $contentRelDir,
            'WP_CONTENT_URL_RELATIVE' => $this->stripDot($contentRelUrlPath),
            'WP_INSTALL_PATH' => $wpRelDir,
            'WP_SITEURL_RELATIVE' => $this->stripDot($wpRelUrlDir),
        ];

        $allVars = array_merge($vars, $this->salter->keys());
        $built = $this->builder->build($paths, 'wp-config.php', $allVars);

        if (!$this->filesystem->writeContent($built, $target)) {
            return self::ERROR;
        }

        if (!$compatMode) {
            return $this->buildLoader($target, $wpConfigLoaderPath, $paths);
        }

        return self::SUCCESS;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return 'Creation of wp-config.php failed.';
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return '<comment>wp-config.php</comment> saved successfully.';
    }

    /**
     * @return bool
     */
    private function askForRegister(): bool
    {
        $lines = [
            'Do you want to register WordPress package wp-content folder '
            . 'as additional theme folder for your project?',
        ];

        return $this->io->askConfirm($lines, true);
    }

    /**
     * @param string $from
     * @param string $to
     * @param bool $bothDirs
     * @return string
     */
    private function relPath(string $from, string $to, bool $bothDirs = true): string
    {
        $path = $this->filesystem->normalizePath(
            $this->filesystem->findShortestPath($from, $to, $bothDirs)
        );

        return rtrim("/{$path}", '/');
    }

    /**
     * @param string $path
     * @return string
     */
    private function stripDot(string $path): string
    {
        $path = ltrim($path, '/');
        strpos($path, './') === 0 and $path = (substr($path, 2) ?: '');
        strpos($path, '../') === 0 and $path = dirname($path);

        return $path;
    }

    /**
     * @param string $wpConfigPath
     * @param string $wpConfigLoaderPath
     * @param Paths $paths
     * @return int
     */
    private function buildLoader(
        string $wpConfigPath,
        string $wpConfigLoaderPath,
        Paths $paths
    ): int {

        $built = $this->builder->build(
            $paths,
            'wp-config-loader.php',
            ['WP_CONFIG_PATH' => $this->relPath($wpConfigLoaderPath, $wpConfigPath, false)]
        );

        return $this->filesystem->writeContent($built, $wpConfigLoaderPath)
            ? Step::SUCCESS
            : Step::ERROR;
    }
}
