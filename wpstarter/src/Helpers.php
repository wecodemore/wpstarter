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
     * @param string   $hook
     * @param callable $callable
     * @param int      $priority
     * @param int      $argsNum
     * @return bool
     */
    public static function addHook($hook, $callable, $priority = 10, $argsNum = 1)
    {
        if (! is_callable($callable)) {
            return;
        }

        if (function_exists('add_filter')) {
            return add_filter($hook, function () use ($callable) {
                $return = call_user_func_array($callable, func_get_args());
                if (! is_null($return)) {
                    return $return;
                }
            }, $priority, $argsNum);
        }

        global $wp_filter;
        is_array($wp_filter) or $wp_filter = [];
        array_key_exists($hook, $wp_filter) or $wp_filter[$hook] = [];
        array_key_exists($priority, $wp_filter[$hook]) or $wp_filter[$hook][$priority] = [];

        /** @var \Closure|object $function */
        $function = is_object($callable)
            ? $callable
            : function () use ($callable) {
                return call_user_func_array($callable, func_get_args());
            };

        $wp_filter[$hook][$priority][spl_object_hash($function)] = [
            'function'      => $function,
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
        if (defined('ABSPATH') && $register) {
            register_theme_directory(ABSPATH.'wp-content/themes');
        }
    }
}
