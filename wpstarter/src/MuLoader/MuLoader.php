<?php declare( strict_types = 1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\MuLoader;

use Exception;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class MuLoader
{
    const EXTRA_KEY = 'wordpress-plugin-main-file';
    const PREFIX = 'wcm_wps_';
    const TRANSIENT = 'data_transient';

    /**
     * @var string[] Supported packages types
     */
    private static $types = array('wordpress-plugin', 'wordpress-muplugin');

    /**
     * @var string[]
     */
    private $plugins = array();

    /**
     * @var string[]
     */
    private $loaded = array();

    /**
     * @var string[]
     */
    private $regular = array();

    /**
     * @var string[]
     */
    private $regularLoaded = array();

    /**
     * Runs on 'muplugins_loaded' hook, with very low priority, and checks for plugins files in
     * subfolder of MU plugin folder. Only plugins that support Composer are taken into account.
     *
     * @param bool $refresh Force plugins data to be loaded from files instead of from transient
     */
    public function __invoke($refresh = false)
    {
        if (!defined('WPMU_PLUGIN_DIR') || !is_dir(WPMU_PLUGIN_DIR) || defined('WP_INSTALLING')) {
            return;
        }
        static $jsonFiles;
        static $transient;
        if (is_null($jsonFiles)) {
            $jsonFiles = glob(WPMU_PLUGIN_DIR.'/*/composer.json', GLOB_NOSORT);

            if (empty($jsonFiles)) {
                return;
            }
            $transient = md5(serialize($jsonFiles));
        }
        $this->plugins = $refresh ? false : get_site_transient(self::PREFIX.$transient);
        if (empty($this->plugins)) {
            $this->plugins = array();
            $refresh = true;
            array_walk($jsonFiles, array($this, 'findFile'));
        }
        $this->loading($refresh, $transient);
    }

    /**
     * Check and load each discovered files.
     *
     * @param bool   $refresh
     * @param string $transient
     */
    private function loading($refresh, $transient)
    {
        $toLoad = array_diff($this->plugins, $this->loaded);
        foreach ($toLoad as $key => $file) {
            $loaded = $this->loadPlugin($key, $file, $refresh, $transient);
            if (!$loaded) {
                break;
            }
        }
        empty($this->regularLoaded) or $this->regularAsMU($this->regularLoaded);
        $this->afterLoading($refresh, $transient);
    }

    /**
     * Check and load a discovered file. Handle problems if file is invalid or unreadable.
     *
     * @param string $key
     * @param string $file
     * @param bool   $refresh
     * @param string $transient
     *
     * @return bool
     */
    private function loadPlugin($key, $file, $refresh, $transient)
    {
        if (is_readable($file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
            wp_register_plugin_realpath($file);
            if (in_array($file, $this->regular, true)) {
                $this->regularLoaded[] = $file;
            }
            include_once $file;
            $this->loaded[] = $file;

            return true;
        }
        if ($refresh) {
            // remove non-readable or non-php files from array of files to be saved
            unset($this->plugins[$key]);

            return true;
        }
        // If here, a non-readable or non-php file is cached: let's delete cache and restart
        delete_site_transient(self::PREFIX.$transient);
        delete_site_transient(self::PREFIX.self::TRANSIENT);
        $this->__invoke(true);

        return false;
    }

    /**
     * For regular plugins used as MU plugins fire activation hooks.
     * Plugins that have deactivation hook CAN'T be used as MU plugins.
     *
     * @param array $plugins
     */
    private function regularAsMU(array $plugins)
    {
        $regular = new PluginAsMuLoader($plugins);
        $regular->install();
    }

    /**
     * Performs operations after loading happened: cache loaded files if needed and add plugins
     * data to mustuse plugin screen.
     *
     * @param bool   $refresh   Does data need to be cached?
     * @param string $transient Transient name
     */
    private function afterLoading($refresh, $transient)
    {
        $refresh and set_site_transient(self::PREFIX.$transient, $this->plugins, WEEK_IN_SECONDS);
        if (!is_admin()) {
            return;
        }
        $loader = $this;
        add_filter('show_advanced_plugins', function ($bool, $type) use ($refresh, $loader) {
            return $loader->showPluginsData($bool, $type, $refresh);
        }, PHP_INT_MAX, 2);
    }

    /**
     * Reads the composer.json of a MU plugin package, and enqueue to be loaded the php file
     * named in the same way of containing subfolder unless it does not exist, in which case
     * findFileInJson() method is used to check for a different file set in
     * "extra.wordpress-plugin-main-file" composer.json config.
     *
     * @param string $jsonFile Full path of composer.json of mu plugin file
     */
    private function findFile($jsonFile)
    {
        try {
            $json = json_decode(file_get_contents($jsonFile), true);
        } catch (Exception $e) { // a bad formed or unreadable composer.json file
            $json = array();
        }
        // if the file for a WordPress (MU) Plugin?
        if (!isset($json['type']) || !in_array($json['type'], self::$types, true)) {
            return;
        }
        $isRegular = $json['type'] === 'wordpress-plugin';
        $basedir = dirname(str_replace('\\', '/', $jsonFile));
        $pluginFile = $basedir.'/'.basename($basedir).'.php';
        if (file_exists($pluginFile)) {
            $this->plugins[] = $pluginFile;
            $isRegular and $this->regular[] = $pluginFile;

            return;
        }
        $this->findFileInJson($json, $basedir, $isRegular);
    }

    /**
     * Check for a plugin file set in "extra.wordpress-plugin-main-file" composer.json config when
     * there's no file with same name of MU plugin subfolder.
     *
     * @param array  $json
     * @param string $basedir
     * @param bool   $isRegular
     */
    private function findFileInJson(array $json, $basedir, $isRegular)
    {
        // check "extra.wordpress-plugin-main-file" in composer.json
        $main = isset($json['extra']) && isset($json['extra'][self::EXTRA_KEY]) ?
            str_replace('\\', '/', $json['extra'][self::EXTRA_KEY])
            : false;
        if ($main) {
            $path = "{$basedir}/{$main}";
            $this->plugins[] = $path;
            $isRegular and $this->regular[] = $path;
        }
    }

    /**
     * Runs on 'show_advanced_plugins' hook to show data of MU plugins loaded by this class.
     *
     * @param bool   $bool
     * @param string $type
     * @param bool   $refresh
     *
     * @return bool
     */
    private function showPluginsData($bool, $type, $refresh)
    {
        $screen = get_current_screen();
        $check = is_multisite() ? 'plugins-network' : 'plugins';
        static $show;
        if ($type === 'mustuse') {
            $show = $bool;                          // does user want to show mustuse plugins?
        } elseif (
            $type === 'dropins'                     // dropins are checked after mustuse
            && $show                                // if user want show mustuse plugins
            && $screen->base === $check             // we are in right screen
            && current_user_can('activate_plugins') // and user has right capabilities
        ) {
            global $plugins;
            // let's merge plugins data discovered by WordPress with plugins data discovered by us
            $plugins['mustuse'] = array_merge($plugins['mustuse'], $this->getPluginsData($refresh));
            uasort($plugins['mustuse'], '_sort_uname_callback');
        }

        return $bool;
    }

    /**
     * Get plugins data from transient or from plugins headers (if available).
     *
     * @param bool $refresh Should data should be cached in transient
     *
     * @return array
     */
    private function getPluginsData($refresh)
    {
        $data = $refresh ? array() : (get_site_transient(self::PREFIX.self::TRANSIENT) ?: array());
        foreach ($this->plugins as $file) {
            $key = basename($file);
            $data[$key] = $this->getPluginData($key, $file);
        }
        $refresh and set_site_transient(self::PREFIX.self::TRANSIENT, $data, WEEK_IN_SECONDS);

        return $data;
    }

    /**
     * @param string $key
     * @param string $file
     *
     * @return array
     */
    private function getPluginData($key, $file)
    {
        $plugin_data = get_plugin_data($file, false, false);
        empty($plugin_data['Name']) and $plugin_data['Name'] = $key;
        in_array($file, $this->regularLoaded, true) and $plugin_data['Name'] .= '*';

        return $plugin_data;
    }
}
