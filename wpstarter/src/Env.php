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

use Dotenv;

/**
 * Extends Dotenv to load and store all environment variables.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WP Starter
 */
class Env extends Dotenv
{
    /**
     * @var array
     */
    private static $set = array();

    /**
     * @var bool
     */
    private static $loaded = false;

    /**
     * @var array
     */
    private static $isBool = array(
        'ALLOW_UNFILTERED_UPLOADS',
        'ALTERNATE_WP_CRON',
        'AUTOMATIC_UPDATER_DISABLED',
        'ALLOW_SUBDIRECTORY_INSTALL',
        'COMPRESS_CSS',
        'COMPRESS_SCRIPTS',
        'CONCATENATE_SCRIPTS',
        'CORE_UPGRADE_SKIP_NEW_BUNDLED',
        'DIEONDBERROR',
        'DISABLE_WP_CRON',
        'DISALLOW_FILE_EDIT',
        'DISALLOW_FILE_MODS',
        'DISALLOW_UNFILTERED_HTML',
        'DO_NOT_UPGRADE_GLOBAL_TABLES',
        'ENFORCE_GZIP',
        'IMAGE_EDIT_OVERWRITE',
        'MEDIA_TRASH',
        'MULTISITE',
        'FORCE_SSL_LOGIN',
        'FORCE_SSL_ADMIN',
        'FTP_SSH',
        'FTP_SSL',
        'SAVEQUERIES',
        'SCRIPT_DEBUG',
        'SUBDOMAIN_INSTALL',
        'WP_ALLOW_MULTISITE',
        'WP_ALLOW_REPAIR',
        'WP_AUTO_UPDATE_CORE',
        'WP_HTTP_BLOCK_EXTERNAL',
        'WP_CACHE',
        'WP_DEBUG',
        'WP_DEBUG_DISPLAY',
        'WP_DEBUG_LOG',
        'WPMU_ACCEL_REDIRECT',
        'WPMU_SENDFILE',
    );

    /**
     * @var array
     */
    private static $isInt = array(
        'AUTOSAVE_INTERVAL',
        'EMPTY_TRASH_DAYS',
        'FS_TIMEOUT',
        'FS_CONNECT_TIMEOUT',
        'WP_CRON_LOCK_TIMEOUT',
        'WP_MAIL_INTERVAL',
        'SITE_ID_CURRENT_SITE',
        'BLOG_ID_CURRENT_SITE',
        'WP_PROXY_PORT',
    );

    private static $isString = array(
        "ABSPATH",
        "ADMIN_COOKIE_PATH",
        "AUTH_COOKIE",
        "BLOGUPLOADDIR",
        "COOKIEHASH",
        "COOKIEPATH",
        "COOKIE_DOMAIN",
        "CUSTOM_USER_META_TABLE",
        "CUSTOM_USER_TABLE",
        "DB_CHARSET",
        "DB_COLLATE",
        "DB_HOST",
        "DB_NAME",
        "DB_PASSWORD",
        "DB_TABLE_PREFIX",
        "DB_USER",
        "DOMAIN_CURRENT_SITE",
        "ERRORLOGFILE",
        "FS_METHOD",
        "FTP_BASE",
        "FTP_CONTENT_DIR",
        "FTP_HOST",
        "FTP_PASS",
        "FTP_PLUGIN_DIR",
        "FTP_PRIKEY",
        "FTP_PUBKEY",
        "FTP_SSH",
        "FTP_SSL",
        "FTP_USER",
        "LOGGED_IN_COOKIE",
        "MU_BASE",
        "NOBLOGREDIRECT",
        "PASS_COOKIE",
        "PATH_CURRENT_SITE",
        "PLUGINS_COOKIE_PATH",
        "SECURE_AUTH_COOKIE",
        "SITECOOKIEPATH",
        "TEST_COOKIE",
        "UPLOADBLOGSDIR",
        "UPLOADS",
        "USER_COOKIE",
        "WPLANG",
        "WPMU_PLUGIN_DIR",
        "WPMU_PLUGIN_URL",
        "WP_ACCESSIBLE_HOSTS",
        "WP_CONTENT_DIR",
        "WP_CONTENT_URL",
        "WP_DEFAULT_THEME",
        "WP_HOME",
        "WP_LANG_DIR",
        "WP_MAX_MEMORY_LIMIT",
        "WP_MEMORY_LIMIT",
        "WP_PLUGIN_DIR",
        "WP_PLUGIN_URL",
        "WP_PROXY_BYPASS_HOSTS",
        "WP_PROXY_HOST",
        "WP_PROXY_PASSWORD",
        "WP_PROXY_USERNAME",
        "WP_SITEURL",
        "WP_TEMP_DIR",
    );

    /**
     * @var array
     */
    private static $isBoolOrInt = array('WP_POST_REVISIONS');

    /**
     * @var array
     */
    private static $isMod = array('FS_CHMOD_DIR', 'FS_CHMOD_FILE');

    /**
     * @var array
     */
    private static $all;

    /**
     * @inheritdoc
     */
    public static function load($path)
    {
        if (! self::$loaded) {
            if (is_null(self::$all)) {
                self::$all = array_merge(
                    self::$isBool,
                    self::$isBoolOrInt,
                    self::$isInt,
                    self::$isMod,
                    self::$isString
                );
            }
            parent::load($path);
            self::$loaded = true;
        }
    }

    /**
     * Set a variable using putenv() and $_ENV.
     *
     * The environment variable value is stripped of single and double quotes.
     *
     * @param string      $name
     * @param string|null $value
     */
    public static function setEnvironmentVariable($name, $value = null)
    {
        list($normName, $normValue) = self::normalise($name, $value);

        if (! is_null($normName) && ! is_null($normValue) && ! isset(self::$set[$normName])) {
            putenv("{$normName}={$normValue}");
            $_ENV[$normName] = $normValue;
            in_array($normName, self::$all, true) and self::$set[$normName] = $normValue;
        }
    }

    /**
     * Return all vars have been set
     *
     * @return array
     */
    public static function all()
    {
        return self::$set;
    }

    /**
     * Check constants values and return a 2 items array name/value.
     * Invalid values are returned as null.
     *
     * @param  string $name
     * @param  string $value
     * @return array
     */
    private static function normalise($name, $value)
    {
        list($normName, $normValue) = parent::normaliseEnvironmentVariable($name, $value);

        if (empty($normName) || is_null($normValue)) {
            return array(null, null);
        }

        switch (true) {
            case in_array($normName, self::$isInt, true):
                $filtered = filter_var($normValue, FILTER_VALIDATE_INT);
                $normValue = $filtered === false ? null : (int) $filtered;
                break;
            case in_array($normName, self::$isBool, true):
                $normValue = (bool) filter_var($normValue, FILTER_VALIDATE_BOOLEAN);
                break;
            case in_array($normName, self::$isBoolOrInt, true) :
                if (is_numeric($normValue)) {
                    $filtered = filter_var($normValue, FILTER_VALIDATE_INT);
                    $normValue = $filtered === false ? null : (int) $filtered;
                    break;
                }
                $normValue = (bool) filter_var($normValue, FILTER_VALIDATE_BOOLEAN);
                break;
            case in_array($normName, self::$isMod, true) :
                $normValue = self::checkMod($normValue);
                break;
        }

        return array($normName, $normValue);
    }

    /**
     * Checks that a value is a valid string representation of octal int file permission code.
     *
     * @param  string   $mod
     * @return int|null
     */
    private static function checkMod($mod)
    {
        if ($mod[0] === '0') {
            $mod = substr($mod, 1);
        }

        return strlen($mod) === 3 && str_replace(range(1, 7), '', $mod) === ''
            ? octdec($mod)
            : null;
    }
}