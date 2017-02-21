<?php declare( strict_types = 1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
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
 */
class Helpers
{
    /**
     * Load all the environment variables using Dotenv class and return them.
     *
     * @param string $dir
     * @param string $file
     *
     * @return array
     */
    public static function settings($dir, $file = '.env')
    {
        $env = Env\Env::load($dir, $file);

        $settings = $env->allVars();

        $required = array(
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD',
        );

        foreach ($required as $key) {
            if (!isset($settings[$key]) || empty($settings[$key])) {
                $names = implode(', ', $required);
                throw new \RuntimeException($names.' environment variables are required.');
            }
        }

        return $settings;
    }

    /**
     * Add an action/filter before WordPress environment is loaded.
     *
     * @param string   $hook
     * @param callable $callable
     * @param int      $priority
     * @param int      $argsNum
     */
    public static function addHook($hook, $callable, $priority = 10, $argsNum = 1)
    {
        // sanity check
        if (
            !is_callable($callable)
            || !is_scalar($hook)
            || !$hook
            || !is_numeric($priority)
            || !is_int($argsNum)) {

            return;
        }

        $hook = (string)$hook;

        if (function_exists('add_filter')) {
            add_action($hook, $callable, $priority, $argsNum);

            return;
        }

        if (
            defined(ABSPATH)
            && is_file(ABSPATH.'wp-includes/class-wp-hook.php')
            && is_file(ABSPATH.'wp-includes/plugin.php')
        ) {
            require_once ABSPATH.'wp-includes/plugin.php';
            if (class_exists('WP_Hook')) {
                add_filter($hook, $callable, $priority, $argsNum);

                return;
            }
        }

        global $wp_filter;
        if (!is_array($wp_filter)) {
            $wp_filter = array();
        }
        if (!isset($wp_filter[$hook])) {
            $wp_filter[$hook] = array();
        }
        if (!isset($wp_filter[$hook][$priority])) {
            $wp_filter[$hook][$priority] = array();
        }
        /** @var \Closure|object $function */
        $function = is_object($callable)
            ? $callable
            : function () use ($callable) {
                return call_user_func_array($callable, func_get_args());
            };
        $wp_filter[$hook][$priority][spl_object_hash($function)] = array(
            'function' => $function,
            'accepted_args' => $argsNum,
        );
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
