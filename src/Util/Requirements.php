<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Config as ComposerConfig;
use WeCodeMore\WpStarter\ComposerPlugin;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Config\Validator;

final class Requirements
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var string
     */
    private $wpVersion;

    /**
     * @var Filesystem
     */
    private $composerFilesystem;

    /**
     * @var ComposerConfig
     */
    private $composerConfig;

    /**
     * @var IOInterface
     */
    private $composerIo;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param string $wpVersion
     */
    public function __construct(Composer $composer, IOInterface $io, string $wpVersion)
    {
        $this->init($composer, $io);
        $this->wpVersion = $wpVersion;
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
     * @return ComposerConfig
     */
    public function composerConfig(): ComposerConfig
    {
        return $this->composerConfig;
    }

    /**
     * @return IOInterface
     */
    public function composerIo(): IOInterface
    {
        return $this->composerIo;
    }

    /**
     * @return Filesystem
     */
    public function composerFilesystem(): Filesystem
    {
        return $this->composerFilesystem;
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    private function init(Composer $composer, IOInterface $io)
    {
        $config = $this->extractConfig($composer);

        empty($config[Config::CUSTOM_STEPS]) and $config[Config::CUSTOM_STEPS] = [];
        empty($config[Config::SCRIPTS]) and $config[Config::SCRIPTS] = [];

        $config[Config::WP_VERSION] = $this->wpVersion;
        $config[Config::MU_PLUGIN_LIST] = (new MuPluginList($composer))->pluginsList();
        $config[Config::COMPOSER_CONFIG] = $composer->getConfig()->all();
        $config[Config::WP_CLI_EXECUTOR] = null;

        $this->composerFilesystem = new Filesystem();
        $this->paths = new Paths($composer, $this->composerFilesystem);
        $this->config = new Config($config, new Validator($this->paths, $this->composerFilesystem));
        $this->io = new Io($io);
        $this->composerIo = $io;
        $this->composerConfig = Factory::createConfig($io);
        $this->paths->initTemplates($this->config);
    }

    /**
     * @param Composer $composer
     * @return array
     */
    private function extractConfig(Composer $composer): array
    {
        $extra = (array)$composer->getPackage()->getExtra();

        $configs = empty($extra[ComposerPlugin::EXTRA_KEY])
            ? []
            : $extra[ComposerPlugin::EXTRA_KEY];

        $dir = getcwd() . DIRECTORY_SEPARATOR;

        $file = is_string($configs) ? trim(basename($configs), '/\\') : 'wpstarter.json';

        // Extract config from a separate JSON file
        $fileConfigs = null;
        if (is_file($dir . $file) && is_readable($dir . $file)) {
            $content = @file_get_contents($dir . $configs);
            $fileConfigs = $content ? @json_decode($content, true) : null;
            is_object($fileConfigs) and $fileConfigs = get_object_vars($fileConfigs);
        }

        is_object($configs) and $configs = get_object_vars($configs);
        is_array($configs) or $configs = [];
        $fileConfigs and $configs = array_merge($configs, $fileConfigs);

        return $configs;
    }
}
