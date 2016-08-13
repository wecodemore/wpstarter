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
 * @package WPStarter
 */
class MuLoader
{
    const PREFIX = 'wcm_wps_';
    const DATA_TRANSIENT = 'plugins_data';
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
        if (!defined('WPMU_PLUGIN_DIR') || !is_dir(WPMU_PLUGIN_DIR) || defined('WP_INSTALLING')) {
            return;
        }
        static $phpFiles;
        static $transient;
        if (is_null($phpFiles)) {
            $phpFiles = glob(WPMU_PLUGIN_DIR . "/*/*.php");
            if (empty($phpFiles)) {
                return;
            }
        }
        if (is_null($transient)) {
            $edited = @filemtime(WPMU_PLUGIN_DIR . '/.');
            $edited or $edited = time();
            $transient = md5(__CLASS__ . $edited);
        }

        $refresh or $this->plugins = get_site_transient(self::PREFIX . $transient);
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
     * @param bool $refresh
     * @param string $transient
     */
    private function loadPlugins($refresh, $transient)
    {
        $toTrigger = [];
        foreach ($this->plugins as $key => $plugin) {
            list($file, $mu) = $plugin;
            $loaded = $this->loadPlugin($key, $file, $refresh, $transient);
            if (!$loaded) {
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
     * @param  bool $refresh
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
        delete_site_transient(self::PREFIX . $transient);
        delete_site_transient(self::PREFIX . self::DATA_TRANSIENT);

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
        $triggered = get_site_option(MuLoader::PREFIX . self::INSTALLED_OPTION, []);
        $valid = array_intersect($triggered, $plugins);
        $toTrigger = array_diff($plugins, $triggered);

        array_walk($toTrigger, function ($plugin) use (&$valid) {
            $basename = plugin_basename($plugin);
            do_action("activate_{$basename}");
            is_multisite() and do_action('activated_plugin', $basename, true);
            $valid[] = $plugin;
        });

        if ($valid !== $triggered) {
            update_site_option(MuLoader::PREFIX . self::INSTALLED_OPTION, $valid);
        }
    }

    /**
     * Performs operations after loading happened.
     *
     * Cache loaded files if needed and add plugins data to MU plugin screen.
     *
     * @param bool $refresh Does data need to be cached?
     * @param string $transient Transient name
     */
    private function afterLoading($refresh, $transient)
    {
        $refresh and set_site_transient(self::PREFIX . $transient, $this->plugins, DAY_IN_SECONDS);
        if (is_admin()) {
            add_filter('show_advanced_plugins', function ($bool, $type) use ($refresh) {
                return $this->showPluginsData($bool, $type, $refresh);
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
        if (!is_file($phpFile) || !is_readable($phpFile)) {
            return;
        }

        $phpFile = wp_normalize_path($phpFile);
        $dirname = dirname($phpFile);
        $this->guessMuPluginFile($dirname);

        if (!array_key_exists($dirname, $this->folders)) {
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
            $this->plugins[] = [$phpFile, $this->maybeIsMu($dirname)];
            $this->folders[$dirname]['done'] = true;
        }
    }

    /**
     * Sets the unique PHP file in a folder as the plugin file to load, if not already set.
     *
     * It is possible that a "real" MU plugin has no plugin headers.
     * In that case, if its folder contains more than one file, we don't know what to load and so
     * we load nothing.
     * In case the folder contains just one PHP file, we assume that's the file to load.
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
            $pluginFile = $lastData['done'] || $lastData['count'] !== 1 ? null : $lastData['last'];

            if (is_file($pluginFile)) {
                $this->folders[$this->lastParsedFolder]['done'] = true;
                $this->plugins[] = [$pluginFile, true];
            }
        }
    }

    /**
     * Try to discover if a plugin file is a MU plugin.
     *
     * When a file with plugins headers is found, we try to understand if it's a MU plugin,
     * because if not we will trigger installation hooks @see handleInstallHooks().
     * A MU plugin with no `composer.json` and proper `"type"` setting will be considered a
     * regular plugin, so installation hooks will be triggered.
     * Should not explode, but no warranties.
     *
     * @param string $path
     * @return bool
     */
    private function maybeIsMu($path)
    {
        // If the folder contains more than one PHP file, it can't be a MU plugin, it must be
        // a regular plugin used as MU plugin.

        if (isset($this->folders[$path]['count']) && $this->folders[$path]['count'] > 1) {
            return false;
        }

        $glob = glob($path . '/*.php');
        if (count($glob) > 1) {
            return false;
        }

        // If the plugin path contains just one file, at this point we can't say it is a MU plugin
        // or not, because WordPress has no plugin header to specify it's a MU plugin, so now we try
        // to look at `composer.json` `"type"` setting, if any `composer.json` is there.

        $composer = $path . '/composer.json';
        if (!is_file($composer) || !is_readable($composer)) {
            return false;
        }

        try {
            $decoded = @json_decode(@file_get_contents($composer), true);

            return
                is_array($decoded)
                && array_key_exists('key', $decoded)
                && $decoded['type'] === 'wordpress-muplugin';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Runs on 'show_advanced_plugins' hook to show data of MU plugins loaded by this class.
     *
     * @param  bool $bool
     * @param  string $type
     * @param  bool $refresh
     * @return bool
     */
    private function showPluginsData($bool, $type, $refresh)
    {
        $screen = get_current_screen();
        $check = is_multisite() ? 'plugins-network' : 'plugins';
        static $show;
        if ($type === 'mustuse') {
            $show = $bool;
        } elseif (
            $type === 'dropins'                     // dropins are checked just after mustuse
            && $show
            && $screen->base === $check
            && current_user_can('activate_plugins')
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
        $data = $refresh ? [] : get_site_transient(self::PREFIX . self::DATA_TRANSIENT);

        is_array($data) or $data = [];
        $oldKeys = $data ? array_keys($data) : [];
        foreach ($this->plugins as $plugin) {
            list($file, $mu) = $plugin;
            $key = basename($file);
            // remove current  key from old keys, if there
            $oldKeys and $oldKeys = array_diff($oldKeys, [$key]);
            // get fresh plugin data if not in transient
            if (empty($data[$key]) || !is_array($data[$key]) || !isset($data[$key]['Name'])) {
                $data[$key] = $this->getPluginData($key, $file, $mu);
            }
        }

        // `$oldKeys` is not empty when `$refresh` is false, but transient has data for plugins
        // that are not loaded anymore
        $oldKeys and $refresh = true;
        foreach ($oldKeys as $key) {
            unset($data[$key]);
        }

        $refresh and set_site_transient(self::PREFIX . self::DATA_TRANSIENT, $data,
            WEEK_IN_SECONDS);

        return $data;
    }

    /**
     * Get plugin headers to be shown on admin screen.
     *
     * Append `*` to names of those regular plugins we loaded as MU plugin.
     * Don't append anything to plugins we loaded uisng this class but were recognized as MU plugin.
     *
     * @see guessMuPluginFile()
     * @see maybeIsMu()
     *
     * @param  string $key
     * @param  string $file
     * @param  bool $isMu
     * @return array
     */
    private function getPluginData($key, $file, $isMu)
    {
        $plugin_data = get_plugin_data($file, false, false);
        if (empty($plugin_data['Name'])) {
            $plugin_data['Name'] = $key;
            $isMu = true; // if we're loading a file with no plugins headers, it must be a MU plugin
        }
        $plugin_data['Name'] .= $isMu ? '' : '*';

        return $plugin_data;
    }
}
