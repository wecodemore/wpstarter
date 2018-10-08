<?php declare(strict_types=1);
/**
 * Plugin Name: WP Starter MU plugins loader.
 * Description: MU plugins loaded from subfolders: {{MU_PLUGINS_LIST}}}.
 */

foreach (explode(',', '{{{MU_PLUGINS_LIST}}}') as $file) {
    $file = wp_normalize_path(__DIR__ . '/' . trim($file));

    // Skip unexistent, unreadable and non-php files
    if ($file
        && is_file($file)
        && is_readable($file)
        && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php'
        && validate_file($file) === 0
    ) {
        require_once $file;
    }
}
