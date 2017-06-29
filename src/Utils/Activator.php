<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Utils;

use Composer\Composer;
use Composer\IO\IOInterface;
use WeCodeMore\WpStarter\ComposerPlugin;


/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WeCodeMore\WpStarter
 * @license http://opensource.org/licenses/MIT MIT
 */
class Activator
{

    /**
     * @var \WeCodeMore\WpStarter\Utils\Config
     */
    private $config;

    /**
     * @var \WeCodeMore\WpStarter\Utils\IO
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Paths
     */
    private $paths;

    /**
     * @var string
     */
    private $wpVersion;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @param string $wpVersion
     */
    public function __construct(Composer $composer, IOInterface $io, $wpVersion)
    {
        $this->init($composer, $io);
        $this->wpVersion = $wpVersion;
    }

    /**
     * @return Config
     */
    public function config()
    {
        return $this->config;
    }

    /**
     * @return IO
     */
    public function io()
    {
        return $this->io;
    }

    /**
     * @return Paths
     */
    public function paths()
    {
        return $this->paths;
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    private function init(Composer $composer, IOInterface $io)
    {
        $configs = $this->extractConfig($composer);

        empty($configs[Config::CUSTOM_STEPS]) and $configs[Config::CUSTOM_STEPS] = [];
        empty($configs[Config::SCRIPS]) and $configs[Config::SCRIPS] = [];

        if (!array_key_exists(Config::VERBOSITY, $configs)) {
            $configs[Config::VERBOSITY] = ($io->isDebug() || $io->isVeryVerbose()) ? 2 : 1;
        }

        $configs[Config::WP_VERSION] = $this->wpVersion;
        $configs[Config::MU_PLUGIN_LIST] = (new MuPluginList($composer))->pluginList();
        $configs[Config::COMPOSER_CONFIG] = $composer->getConfig()->all();
        $configs[Config::WP_CLI_EXECUTOR] = null;

        $this->paths = new Paths($composer);
        $this->config = new Config($configs);

        $this->io = new IO($io, $this->config[Config::VERBOSITY]);
    }

    /**
     * @param Composer $composer
     * @return array
     */
    private function extractConfig(Composer $composer)
    {
        $extra = (array)$composer->getPackage()->getExtra();

        $configs = empty($extra[ComposerPlugin::EXTRA_KEY])
            ? []
            : $extra[ComposerPlugin::EXTRA_KEY];

        $dir = getcwd() . DIRECTORY_SEPARATOR;

        // Extract config from a separate JSON file
        if (is_string($configs) && is_file($dir . $configs) && is_readable($dir . $configs)) {
            $content = @file_get_contents($dir . $configs);
            $configs = $content ? @json_decode($content) : [];
        }

        if (!is_array($configs)) {
            $configs = is_object($configs) ? get_object_vars($configs) : [];
        }

        $override = empty($extra[ComposerPlugin::EXTRA_KEY_OVERRIDE])
            ? []
            : $extra[ComposerPlugin::EXTRA_KEY_OVERRIDE];

        if (!is_array($override)) {
            $override = is_object($override) ? get_object_vars($override) : [];
        }

        $override and $configs = array_merge($configs, $override);

        return $configs;
    }
}