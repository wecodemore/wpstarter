<?php declare(strict_types=1);
/**
 * Plugin Name: WP Starter MU plugins loader.
 * Description: MU plugins loaded: {{MU_PLUGINS_LIST}}}.
 */

foreach (explode(',', '{{{MU_PLUGINS_LIST}}}') as $muPlugin) {
    $filePath = wp_normalize_path(trim($muPlugin));
    // Skip unexistent, unreadable and non-php files
    if ($filePath
        && is_file($filePath)
        && is_readable($filePath)
        && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'php'
        && validate_file($filePath) === 0
    ) {
        require_once $filePath;
    }
}
