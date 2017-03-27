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

/**
 * Helpers functions.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class Helpers
{
    /**
     * Add an action/filter before WordPress environment is loaded.
     *
     * @param string $hook
     * @param callable $callable
     * @param int $priority
     * @param int $argsNum
     * @return bool
     */
    public static function addHook($hook, callable $callable, $priority = 10, $argsNum = 1)
    {
        if (function_exists('add_filter')) {
            return add_filter($hook, $callable, $priority, $argsNum);
        }

        global $wp_filter;
        is_array($wp_filter) or $wp_filter = [];
        array_key_exists($hook, $wp_filter) or $wp_filter[$hook] = [];
        array_key_exists($priority, $wp_filter[$hook]) or $wp_filter[$hook][$priority] = [];

        $function = $callable;
        if (!is_object($function)) {
            $function = function (...$args) use ($callable) {
                return $callable(...$args);
            };
        }

        $wp_filter[$hook][$priority][spl_object_hash($function)] = [
            'function' => $callable,
            'accepted_args' => $argsNum,
        ];

        return true;
    }

    /**
     * Register default themes inside WordPress package wp-content folder.
     *
     * @param bool $register
     */
    public static function loadThemeFolder($register = true)
    {
        if ($register && defined('ABSPATH')) {
            register_theme_directory(ABSPATH . 'wp-content/themes');
        }
    }
}
