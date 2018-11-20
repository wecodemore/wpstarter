<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\ComposerPlugin;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Config\Validator;

/**
 * Sort of factory and service locator for objects that are required for WP Starter bootstrapping.
 */
final class Requirements
{
    const CONFIG_FILE = 'wpstarter.json';

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
     * @param SelectedStepsFactory $factory
     * @param bool $autorun
     * @param bool $update
     * @param PackageInterface[] $updatedPackages
     */
    public function __construct(
        Composer $composer,
        IOInterface $io,
        Filesystem $filesystem,
        SelectedStepsFactory $factory,
        bool $autorun,
        bool $update,
        array $updatedPackages
    ) {

        $this->filesystem = $filesystem;

        $extra = $composer->getPackage()->getExtra();

        $this->paths = new Paths($composer->getConfig(), $extra, $filesystem);
        $root = $this->paths->root();

        $config = $this->extractConfig($root, $extra);
        $isSelectedCommandMode =  $factory->isSelectedCommandMode();
        $config[Config::IS_WPSTARTER_COMMAND] = !$autorun;
        $config[Config::IS_WPSTARTER_SELECTED_COMMAND] = !$autorun && $isSelectedCommandMode;
        $config[Config::IS_COMPOSER_UPDATE] = $autorun && $update;
        $config[Config::IS_COMPOSER_INSTALL] = $autorun && !$update;
        $config[Config::COMPOSER_UPDATED_PACKAGES] = $updatedPackages;

        $this->config = new Config($config, new Validator($this->paths, $filesystem));
        $this->io = new Io($io);

        $templatesDir = $this->config[Config::TEMPLATES_DIR];
        $templatesDir->notEmpty() and $this->paths->useCustomTemplatesDir($templatesDir->unwrap());
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
}
