<?php declare(strict_types=1);
/**
 * Plugin Name: WP Starter MU plugins loader.
 * Description: MU plugins loaded: {{{MU_PLUGINS_LIST}}}.
 */

foreach (explode(',', '{{{MU_PLUGINS_LIST}}}') as $muPlugin) {
    $filePath = wp_normalize_path(trim($muPlugin));
    if ($filePath && file_exists(__DIR__ . "/{$filePath}")) {
        require_once __DIR__ . "/{$filePath}";
    }
}
