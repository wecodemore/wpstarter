<?php declare(strict_types=1);

/**
 * Plugin Name: WP Starter MU plugins loader
 * Description: Loads MU plugins installed by Composer in subfolders.
 */

$wpStarterMuPlugins = [];
foreach (explode(',', '{{{MU_PLUGINS_LIST}}}') as $wpStarterMuPlugin) {
    $wpStarterMuPlugin = trim($wpStarterMuPlugin);
    // Like WordPress does, not using __DIR__ that resolves symlinks.
    $wpStarterMuPluginFile = WPMU_PLUGIN_DIR . "/{$wpStarterMuPlugin}";
    if (($wpStarterMuPlugin !== '') && is_file($wpStarterMuPluginFile)) {
        $wpStarterMuPlugins[$wpStarterMuPlugin] = $wpStarterMuPluginFile;
    }
}

// Requires WP 6.3+. Does nothing if the Must-Use tab is hidden via `show_advanced_plugins`.
add_filter(
    'plugins_list',
    static function ($plugins) use ($wpStarterMuPlugins) {
        if (empty($plugins['mustuse']) || !is_array($plugins['mustuse'])) {
            return $plugins;
        }
        foreach ($wpStarterMuPlugins as $key => $file) {
            $data = get_plugin_data($file, false, false);
            if (($data['Name'] ?? '') === '') {
                $data['Name'] = basename(dirname($file));
            }
            $plugins['mustuse'][$key] = $data;
        }
        uasort($plugins['mustuse'], '_sort_uname_callback');

        return $plugins;
    }
);

// Not in the description, which WordPress translates using the plugin's text domain.
add_filter(
    'plugin_row_meta',
    static function ($meta, $file) use ($wpStarterMuPlugins) {
        if (is_array($meta) && isset($wpStarterMuPlugins[$file])) {
            $meta[] = 'Loaded by WP Starter MU plugins loader';
        }
        return $meta;
    },
    10,
    2
);

// Same loading logic as in wp-settings.php
foreach ($wpStarterMuPlugins as $wpStarterMuPlugin) {
    $wpStarterMuPluginFile = $wpStarterMuPlugin;
    require_once $wpStarterMuPlugin;
    $wpStarterMuPlugin = $wpStarterMuPluginFile; // Avoid stomping of the variable in a plugin.

    do_action('mu_plugin_loaded', $wpStarterMuPlugin);
}

unset($wpStarterMuPlugin, $wpStarterMuPluginFile, $wpStarterMuPlugins);
