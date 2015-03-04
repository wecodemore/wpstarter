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

class Helpers
{
    private static $env_loaded = false;

    /**
     * Load all the environment variables using Dotenv class and return them.
     *
     * @param $dir
     * @return array
     */
    public static function settings($dir)
    {
        Env::load($dir);
        Env::required(array('DB_NAME', 'DB_USER', 'DB_PASSWORD'));
        self::$env_loaded = true;

        return Env::all();
    }

    /**
     * Add an action/filter before WordPress environment is loaded.
     *
     * @param string   $hook
     * @param callable $function_to_add
     * @param int      $priority
     * @param int      $accepted_args
     */
    public static function addHook(
        $hook,
        callable $function_to_add,
        $priority = 10,
        $accepted_args = 1
    ) {
        global $wp_filter;
        if (! is_array($wp_filter)) {
            $wp_filter = array();
        }
        if (! isset($wp_filter[$hook])) {
            $wp_filter[$hook] = array();
        }
        if (! isset($wp_filter[$hook][$priority])) {
            $wp_filter[$hook][$priority] = array();
        }
        /** @var \Closure|object $function */
        $function = is_object($function_to_add)
            ? $function_to_add
            : function () use ($function_to_add) {
                return call_user_func_array($function_to_add, func_get_args());
            };
        $id = spl_object_hash($function);
        $wp_filter[$hook][$priority][$id] = array(
            'function'      => $function,
            'accepted_args' => $accepted_args,
        );
    }
}
