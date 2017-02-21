<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\MuLoader;

use RuntimeException;

/**
 * This class handle the cases regular plugins are used as MU plugin.
 * If any of them has a uninstall routine an exception is thrown, because is never possible to call
 * it: MU plugins are deactivated only by deleting the file.
 * For newly added plugins installation hook is fired.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class PluginAsMuLoader
{
    const OPTION = 'plugins_installed';

    /**
     * @var array
     */
    private $plugins;

    /**
     * @var array
     */
    private $uninstall;

    /**
     * Constructor.
     *
     * Receives an array of regular plugins files used as MU plugins, checks which of them have
     * never be installed (looking at a site option) and launches installation routine for them.
     *
     * @param array $plugins
     */
    public function __construct(array $plugins)
    {
        if (!empty($plugins)) {
            $installed = get_site_option(MuLoader::PREFIX.self::OPTION, array());
            $toInstall = array_diff($plugins, $installed);
            if ($toInstall !== $installed) {
                update_site_option(MuLoader::PREFIX.self::OPTION, $toInstall);
            }
            $this->plugins = $toInstall;
        }
    }

    /**
     * Loop through plugins files, and launch install routine.
     */
    public function install()
    {
        if (!empty($this->plugins)) {
            $this->uninstall = (array) get_option('uninstall_plugins', array());
            array_walk($this->plugins, array($this, 'installPlugin'));
        }
    }

    /**
     * Take a plugin file, and check if it has any installation / deactivation routine.
     * If so thrown an exception, if not fires activation hooks.
     *
     * @param string $plugin
     */
    private function installPlugin($plugin)
    {
        $basename = plugin_basename($plugin);
        $isUninstall = array_key_exists($this->uninstall, $basename);
        if (
            has_action("deactivate_{$basename}")
            || file_exists(dirname($plugin).'/uninstall.php')
            || $isUninstall
        ) {
            $this->error($basename, $isUninstall);
        }
        do_action("activate_{$basename}");
        is_multisite() and do_action('activated_plugin', $basename, true);
    }

    /**
     * Thrown an exception when a plugin that has deactivation/uninstall routine is used
     * as MU plugin. Update 'uninstall_plugins' option removing plugin file if there.
     *
     * @param string $plugin
     * @param bool   $isUninstall
     */
    private function error($plugin, $isUninstall)
    {
        if ($isUninstall) {
            unset($this->uninstall[$plugin]);
            update_option('uninstall_plugins', $this->uninstall);
        }
        throw new RuntimeException(
            "{$plugin} can't be auto-loaded because has deactivate/uninstall routines."
        );
    }
}
