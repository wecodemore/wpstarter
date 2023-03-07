<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\ComposerPlugin;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Config\Validator;
use WeCodeMore\WpStarter\Io\Formatter;
use WeCodeMore\WpStarter\Io\Io;

/**
 * Sort of factory and service locator for objects that are required for WP Starter bootstrapping.
 */
final class Requirements
{
    public const CONFIG_FILE = 'wpstarter.json';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Io
     */
    private $io;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param Filesystem $filesystem
     * @return Requirements
     */
    public static function forGenericCommand(
        Composer $composer,
        IOInterface $io,
        Filesystem $filesystem
    ): Requirements {

        return new static($composer, $io, $filesystem, false, false, false);
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param Filesystem $filesystem
     * @return Requirements
     */
    public static function forSelectedStepsCommand(
        Composer $composer,
        IOInterface $io,
        Filesystem $filesystem
    ): Requirements {

        return new static($composer, $io, $filesystem, true, false, false);
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param Filesystem $filesystem
     * @param PackageInterface ...$updatedPackages
     * @return Requirements
     */
    public static function forComposerInstall(
        Composer $composer,
        IOInterface $io,
        Filesystem $filesystem,
        PackageInterface ...$updatedPackages
    ): Requirements {

        return new static($composer, $io, $filesystem, false, true, false, ...$updatedPackages);
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param Filesystem $filesystem
     * @param PackageInterface ...$updatedPackages
     * @return Requirements
     */
    public static function forComposerUpdate(
        Composer $composer,
        IOInterface $io,
        Filesystem $filesystem,
        PackageInterface ...$updatedPackages
    ): Requirements {

        return new static($composer, $io, $filesystem, false, true, true, ...$updatedPackages);
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param Filesystem $filesystem
     * @param bool $isSelectedCommandMode
     * @param bool $isComposer
     * @param bool $isComposerUpdate
     * @param PackageInterface ...$updatedPackages
     */
    private function __construct(
        Composer $composer,
        IOInterface $io,
        Filesystem $filesystem,
        bool $isSelectedCommandMode,
        bool $isComposer,
        bool $isComposerUpdate,
        PackageInterface ...$updatedPackages
    ) {

        $this->filesystem = $filesystem;

        $extra = $composer->getPackage()->getExtra();

        $this->io = new Io($io, new Formatter());
        $this->paths = new Paths($composer->getConfig(), $extra, $filesystem);
        $root = $this->paths->root();

        $config = $this->extractConfig($root, $extra);
        $config[Config::IS_WPSTARTER_COMMAND] = !$isComposer;
        $config[Config::IS_WPSTARTER_SELECTED_COMMAND] = !$isComposer && $isSelectedCommandMode;
        $config[Config::IS_COMPOSER_UPDATE] = $isComposer && $isComposerUpdate;
        $config[Config::IS_COMPOSER_INSTALL] = $isComposer && !$isComposerUpdate;
        $config[Config::COMPOSER_UPDATED_PACKAGES] = $isComposer ? $updatedPackages : [];
        $config = $this->determineWpConfigPath($config);
        $this->config = new Config($config, new Validator($this->paths, $filesystem));

        $templatesDirConfig = $this->config[Config::TEMPLATES_DIR];
        /** @var string|null $templatesDir */
        $templatesDir = $templatesDirConfig->unwrapOrFallback();
        $templatesDir and $this->paths->useCustomTemplatesDir($templatesDir);
    }

    /**
     * @return Config
     */
    public function config(): Config
    {
        return $this->config;
    }

    /**
     * @return Io
     */
    public function io(): Io
    {
        return $this->io;
    }

    /**
     * @return Paths
     */
    public function paths(): Paths
    {
        return $this->paths;
    }

    /**
     * @return Filesystem
     */
    public function filesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * @param string $rootPath
     * @param array $extra
     * @return array
     */
    private function extractConfig(string $rootPath, array $extra): array
    {
        $configs = empty($extra[ComposerPlugin::EXTRA_KEY])
            ? []
            : $extra[ComposerPlugin::EXTRA_KEY];

        $file = self::CONFIG_FILE;
        $overrideFile = null;
        if (is_string($configs)) {
            $file = ltrim($configs, '/\\');
            $overrideFile = "{$rootPath}/" . self::CONFIG_FILE;
            $configs = [];
        }

        // Extract config from a separate JSON files
        $fileConfigs = null;
        $overrideConfigs = null;
        if (is_file("{$rootPath}/{$file}") && is_readable("{$rootPath}/{$file}")) {
            $content = @file_get_contents("{$rootPath}/{$file}");
            $fileConfigs = $content ? @json_decode($content, true) : null;
        }
        if ($overrideFile && is_file($overrideFile) && is_readable($overrideFile)) {
            $overrideContent = @file_get_contents($overrideFile);
            $overrideConfigs = $overrideContent ? @json_decode($overrideContent, true) : null;
        }

        is_array($configs) or $configs = [];

        $fileConfigs and $configs = array_merge($configs, (array)$fileConfigs);
        $overrideConfigs and $configs = array_merge($configs, (array)$overrideConfigs);

        return $configs;
    }

    /**
     * @param array $config
     * @param Io $io
     * @return array
     */
    private function determineWpConfigPath(array $config): array
    {
        $root = $this->paths->root();
        $wpParent = $this->paths->wpParent();
        if ($root === $wpParent) {
            $config[Config::WP_CONFIG_PATH] = $root;

            return $config;
        }
        $wpConfigPath = $this->paths->wpParent('wp-config.php');
        if (!file_exists($wpConfigPath)) {
            $config[Config::WP_CONFIG_PATH] = $root;

            return $config;
        }
        $content = file_get_contents($wpConfigPath);
        if (strpos($content, 'WordPressEnvBridge') === false) {
            $config[Config::WP_CONFIG_PATH] = $root;

            return $config;
        }

        $config[Config::WP_CONFIG_PATH] = $wpParent;

        return $config;
    }
}
