<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter;

use Exception;
use RuntimeException;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WP Starter
 */
class MuLoader
{
    const EXTRA_KEY = 'wordpress-plugin-main-file';
    const PREFIX    = 'wcm_wps_';
    const TRANSIENT = 'data_transient';
    const OPTION    = 'plugins_installed';

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
    private $regularPlugins = array();

    /**
     * @var string[]
     */
    private $regularPluginsLoaded = array();

    /**
     * Runs on 'muplugins_loaded' hook, with very low priority, and checks for plugins files in
     * subfolder of MU plugin folder. Only plugins that support Composer are taken into account.
     *
     * @param bool $refresh Force plugins data to be loaded from files instead of from transient
     */
    public function __invoke($refresh = false)
    {
        if (
            ! defined('WPMU_PLUGIN_DIR')
            || ! is_dir(WPMU_PLUGIN_DIR)
            || defined('WP_INSTALLING')
        ) {
            return;
        }
        static $jsonFiles;
        static $transient;
        if (is_null($jsonFiles)) {
            $jsonFiles = glob(WPMU_PLUGIN_DIR."/*/composer.json", GLOB_NOSORT);
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
     * Check and load each discovered files. Handle problems if bad/unreadable files are required.
     *
     * @param bool   $refresh
     * @param string $transient
     */
    private function loading($refresh, $transient)
    {
        $toLoad = $this->plugins;
        foreach ($toLoad as $i => $file) {
            if (is_readable($file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
                wp_register_plugin_realpath($file);
                if (in_array($file, $this->regularPlugins, true)) {
                    $this->regularPluginsLoaded[] = $file;
                }
                include_once $file;
            } elseif ($refresh) {
                // remove non-readable or non-php files from array of files to be saved
                unset($this->plugins[$i]);
            } else {
                // If here, a non-readable or non-php file is cached: let's delete cache and restart
                delete_site_transient(self::PREFIX.$transient);
                delete_site_transient(self::PREFIX.self::TRANSIENT);
                $this->__invoke(true);

                return;
            }
        }
        $this->pluginHooks();
        $this->afterLoading($refresh, $transient);
    }

    /**
     * For regular plugins used as MU plugins fire activation hooks.
     * Plugins that have deactivation hook CAN'T be used as MU plugins.
     */
    private function pluginHooks()
    {
        $installed = get_site_option(self::PREFIX.self::OPTION, array());
        $toInstall = array_diff($this->regularPluginsLoaded, $installed);
        if ($toInstall !== $installed) {
            update_site_option(self::PREFIX.self::OPTION, $toInstall);
        }
        if (empty($toInstall)) {
            return;
        }
        $uninstall = get_option('uninstall_plugins', array());
        array_walk($toInstall, function ($plugin) use (&$uninstall) {
            $basename = plugin_basename($plugin);
            $isUninstall = array_key_exists($uninstall, $basename);
            if (
                has_action("deactivate_{$basename}")
                || file_exists(dirname($plugin).'/uninstall.php')
                || $isUninstall
            ) {
                if ($isUninstall) {
                    unset($uninstall[$basename]);
                    update_option('uninstall_plugins', $uninstall);
                }
                throw new RuntimeException(
                    "{$basename} can't be auto-loaded because has deactivate/uninstall routines."
                );
            }
            do_action("activate_{$basename}");
            is_multisite() and do_action('activated_plugin', $basename, true);
        });
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
        if ($refresh) {
            set_site_transient(self::PREFIX.$transient, $this->plugins, WEEK_IN_SECONDS);
        }
        if (is_admin()) {
            $loader = $this;
            add_filter('show_advanced_plugins', function ($bool, $type) use ($refresh, $loader) {
                return $loader->showPluginsData($bool, $type, $refresh);
            }, PHP_INT_MAX, 2);
        }
    }

    /**
     * Reads the composer.json of a MU plugin package, and enqueue to be loaded the php file
     * named in the same way of containing subfolder unless it does not exist, in which case
     * findFileInJson() method is used to check for a different file set in
     * "extra.wordpress-plugin-main-file" composer.json config.
     *
     * @param  string $jsonFile Full path of composer.json of mu plugin file
     * @return void
     * @uses \GM\MuPluginsComposer\FileLoader\findFileInJson()
     */
    private function findFile($jsonFile)
    {
        try {
            $json = json_decode(file_get_contents($jsonFile), true);
        } catch (Exception $e) { // a bad formed or unreadable composer.json file
            $json = array();
        }
        // if the file for a WordPress (MU) Plugin?
        if (isset($json['type']) && in_array($json['type'], self::$types, true)) {
            $isRegular = $json['type'] === 'wordpress-plugin';
            /** @var string $basedir Plugin dir with ensured cross-OS reliability */
            $basedir = dirname(str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $jsonFile));
            /** @var string $pluginFile File to load, default to php file named after subfolder */
            $pluginFile = $basedir.DIRECTORY_SEPARATOR.basename($basedir).'.php';
            if (file_exists($pluginFile)) {
                $this->plugins[] = $pluginFile;
                if ($isRegular) {
                    $this->regularPlugins[] = $pluginFile;
                }
            } else {
                $this->findFileInJson($json, $basedir, $isRegular);
            }
        }
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
        if (isset($json['extra']) && isset($json['extra'][self::EXTRA_KEY])) {
            $path = $basedir
                .DIRECTORY_SEPARATOR
                .str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $json['extra'][self::EXTRA_KEY]);
            $this->plugins[] = $path;
            if ($isRegular) {
                $this->regularPlugins[] = $path;
            }
        }
    }

    /**
     * Runs on 'show_advanced_plugins' hook to show data of MU plugins loaded by this class.
     *
     * @param  bool   $bool
     * @param  string $type
     * @param  bool   $refresh
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
            // let's merge plugins data discovered by WordPress with plugins data discovered by us
            $GLOBALS['plugins']['mustuse'] = array_merge(
                $GLOBALS['plugins']['mustuse'],
                $this->getPluginsData($refresh)
            );
            uasort($GLOBALS['plugins']['mustuse'], '_sort_uname_callback');
            $show = false;
        }

        return $bool;
    }

    /**
     * Get plugins data from transient or from plugins headers (if available).
     *
     * @param  bool  $refresh Should data should be cached in transient
     * @return array
     */
    private function getPluginsData($refresh)
    {
        $data = $refresh ? false : get_site_transient(self::PREFIX.self::TRANSIENT);
        if (empty($data)) {
            $data = array();
            foreach ($this->plugins as $file) {
                $plugin_data = get_plugin_data($file, false, false);
                $key = basename($file);
                if (empty($plugin_data['Name'])) {
                    $plugin_data['Name'] = $key;
                }
                if (in_array($file, $this->regularPluginsLoaded, true)) {
                    $plugin_data['Name'] .= '*';
                }
                $data[$key] = $plugin_data;
            }
            $refresh and set_site_transient(self::PREFIX.self::TRANSIENT, $data, WEEK_IN_SECONDS);
        }

        return $data;
    }
}
