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
     */
    public function __construct(
        Composer $composer,
        IOInterface $io,
        Filesystem $filesystem
    ) {

        $this->filesystem = $filesystem;

        $this->paths = new Paths($composer, $filesystem);
        $root = $this->paths->root();

        $config = $this->extractConfig($root, $composer->getPackage()->getExtra());
        $config[Config::COMPOSER_CONFIG] = $composer->getConfig()->all();

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

        $file = is_string($configs) ? trim(basename($configs), '/\\') : self::CONFIG_FILE;

        // Extract config from a separate JSON file
        $fileConfigs = null;
        if (is_file("{$rootPath}/{$file}") && is_readable("{$rootPath}/{$file}")) {
            $content = @file_get_contents("{$rootPath}/{$file}");
            $fileConfigs = $content ? @json_decode($content, true) : null;
            is_object($fileConfigs) and $fileConfigs = get_object_vars($fileConfigs);
        }

        is_object($configs) and $configs = get_object_vars($configs);
        is_array($configs) or $configs = [];
        $fileConfigs and $configs = array_merge($configs, $fileConfigs);

        return $configs;
    }
}
