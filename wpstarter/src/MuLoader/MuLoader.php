<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\MuLoader;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WP Starter
 */
class MuLoader
{
    const PREFIX           = 'wcm_wps_';
    const DATA_TRANSIENT   = 'plugins_data';
    const INSTALLED_OPTION = 'plugins_installed';

    /**
     * @var array
     */
    private $plugins = [];

    /**
     * @var string[]
     */
    private $folders = [];

    /**
     * @var string
     */
    private $lastParsedFolder = '';

    /**
     * Runs on 'muplugins_loaded' hook, with very low priority, and checks for plugins files in
     * subfolder of MU plugin folder.
     *
     * @param bool $refresh Force plugins data to be loaded from files instead of from transient
     */
    public function __invoke($refresh = false)
    {
        if (! defined('WPMU_PLUGIN_DIR') || ! is_dir(WPMU_PLUGIN_DIR) || defined('WP_INSTALLING')) {
            return;
        }
        static $phpFiles;
        static $transient;
        if (is_null($phpFiles)) {
            $phpFiles = glob(WPMU_PLUGIN_DIR."/*/*.php");
            if (empty($phpFiles)) {
                return;
            }
        }
        if (is_null($transient)) {
            $edited = @filemtime(WPMU_PLUGIN_DIR.'/.');
            $edited or $edited = time();
            $transient = md5(__CLASS__.$edited);
        }

        $refresh or $this->plugins = get_site_transient(self::PREFIX.$transient);
        if (empty($this->plugins)) {
            $this->plugins = [];
            $refresh = true;
            array_walk($phpFiles, [$this, 'findPluginFile']);
        }

        $this->loadPlugins($refresh, $transient);
        $this->afterLoading($refresh, $transient);
    }

    /**
     * Check and load each discovered files.
     *
     * @param bool   $refresh
     * @param string $transient
     */
    private function loadPlugins($refresh, $transient)
    {
        $toTrigger = [];
        foreach ($this->plugins as $key => $plugin) {
            list($file, $mu) = $plugin;
            $loaded = $this->loadPlugin($key, $file, $refresh, $transient);
            if (! $loaded) {
                $this->__invoke(true);
                break;
            }

            $mu or $toTrigger[] = $file;
        }

        $toTrigger and $this->handleInstallHooks($toTrigger);
    }

    /**
     * Check and load a discovered file. Handle problems if file is invalid or unreadable.
     *
     * @param  string $key
     * @param  string $file
     * @param  bool   $refresh
     * @param  string $transient
     * @return bool
     */
    private function loadPlugin($key, $file, $refresh, $transient)
    {
        if (is_readable($file)) {
            wp_register_plugin_realpath($file);
            /** @noinspection PhpIncludeInspection */
            include_once $file;

            return true;
        }

        if ($refresh) {
            // remove non-readable or non-php files from array of files to be saved
            unset($this->plugins[$key]);

            return true;
        }

        // If here, a non-readable or non-php file is cached: let's delete cache and restart
        delete_site_transient(self::PREFIX.$transient);
        delete_site_transient(self::PREFIX.self::DATA_TRANSIENT);

        return false;
    }

    /**
     * Fire activation hooks for regular plugins used as MU plugins.
     *
     * However, regular plugins that have deactivation/activation routines should **NOT** be used a
     * MU plugins, because deactivation routines will never happen (there's no way do deactivate a
     * MU plugin) and activation routines will be triggered by this method first time a plugin file
     * is loaded, which *should* work most of the times, but there's no warranty against
     * explosions.
     *
     * @param array $plugins
     */
    private function handleInstallHooks(array $plugins)
    {
        $installed = get_site_option(MuLoader::PREFIX.self::INSTALLED_OPTION, []);
        $toTrigger = array_diff($plugins, $installed);
        array_walk($toTrigger, function ($plugin) {
            $basename = plugin_basename($plugin);
            do_action("activate_{$basename}");
            is_multisite() and do_action('activated_plugin', $basename, true);
        });

        if ($toTrigger !== $installed) {
            update_site_option(MuLoader::PREFIX.self::INSTALLED_OPTION, $toTrigger);
        }
    }

    /**
     * Performs operations after loading happened.
     *
     * Cache loaded files if needed and add plugins data to MU plugin screen.
     *
     * @param bool   $refresh   Does data need to be cached?
     * @param string $transient Transient name
     */
    private function afterLoading($refresh, $transient)
    {
        $refresh and set_site_transient(self::PREFIX.$transient, $this->plugins, DAY_IN_SECONDS);
        if (is_admin()) {
            $loader = $this;
            add_filter('show_advanced_plugins', function ($bool, $type) use ($refresh, $loader) {
                return $loader->showPluginsData($bool, $type, $refresh);
            }, PHP_INT_MAX, 2);
        }
    }

    /**
     * Looks for the plugin headers of a file, and enqueue it to be loaded headers are found.
     *
     * @param  string $phpFile Full path of candidate plugin file
     * @return void
     */
    private function findPluginFile($phpFile)
    {
        if (! is_file($phpFile) || ! is_readable($phpFile)) {
            return;
        }

        $dirname = dirname($phpFile);
        $this->guessMuPluginFile($dirname);

        if (! array_key_exists($dirname, $this->folders)) {
            $this->folders[$dirname] = ['done' => false, 'count' => 0];
            $this->lastParsedFolder = $dirname;
        }

        $this->folders[$dirname]['count']++;
        $this->folders[$dirname]['last'] = $phpFile;

        // there might be just one plugin per folder
        if ($this->folders[$dirname]['done']) {
            return;
        }

        $data = @get_file_data($phpFile, ['Name' => 'Plugin Name']);
        $data = is_array($data) ? array_change_key_case($data, CASE_LOWER) : [];
        if (array_key_exists('name', $data) && trim($data['name'])) {
            // seems we found the plugin file
            $this->plugins[] = [$phpFile, false];
            $this->folders[$dirname]['done'] = true;
        }
    }

    /**
     * It is possible that a "real" MU plugin has no plugin headers.
     * In that case, if its folder contains more than one file, we don't know what to laod and so
     * we load nothing. In case the folder contains just one PHP file, we assume that's the file to
     * load.
     * No warranties against explosions.
     *
     * @param string $currentDir
     */
    private function guessMuPluginFile($currentDir)
    {
        if (
            $this->lastParsedFolder
            && $currentDir !== $this->lastParsedFolder
            && array_key_exists($this->lastParsedFolder, $this->folders)
        ) {
            $lastData = $this->folders[$this->lastParsedFolder];
            $pluginFile = null;
            if (! $lastData['done'] && $lastData['count'] === 1) {
                $pluginFile = isset($lastData['last']) ? $lastData['last'] : null;
            }

            if (is_file($pluginFile)) {
                $this->folders[$this->lastParsedFolder]['done'] = true;
                $this->plugins[] = [$pluginFile, true];
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
     * @param  bool $refresh Should data should be cached in transient
     * @return array
     */
    private function getPluginsData($refresh)
    {
        $data = $refresh ? [] : get_site_transient(self::PREFIX.self::DATA_TRANSIENT);
        is_array($data) or $data = [];
        foreach ($this->plugins as $plugin) {
            list($file, $mu) = $plugin;
            $key = basename($file);
            $data[$key] = $this->getPluginData($key, $file, $mu);
        }
        $refresh and set_site_transient(self::PREFIX.self::DATA_TRANSIENT, $data, WEEK_IN_SECONDS);

        return $data;
    }

    /**
     * Get plugin headers to be shown on admin screen.
     *
     * Append `*` to names of those plugins we loaded automatically, so we can distinguish them.
     * Append `**` to names of those plugins we loaded "guessing" the file (see `guessMuPlugin()`).
     *
     * @param  string $key
     * @param  string $file
     * @param  bool   $isMu
     * @return array
     */
    private function getPluginData($key, $file, $isMu)
    {
        $plugin_data = get_plugin_data($file, false, false);
        empty($plugin_data['Name']) and $plugin_data['Name'] = $key;
        $plugin_data['Name'] .= $isMu ? '**' : '*';

        return $plugin_data;
    }
}
