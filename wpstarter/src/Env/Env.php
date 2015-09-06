<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Env;


/**
 * Extends Dotenv to load and store all environment variables.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WP Starter
 */
final class Env
{

    /**
     * @var array
     */
    private static $set = array();

    /**
     * @var static
     */
    private static $loaded;

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
     * @var array
     */
    private $vars;

    /**
     * @param string $path
     * @param string $file
     * @return \WCM\WPStarter\Env\Env|static
     */
    public static function load($path, $file = '.env')
    {
        if (is_null(self::$loaded)) {

            self::wpConstants();

            if ( ! is_string($file)) {
                $file = '.env';
            }

            $filePath = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
            $loader = new Loader($filePath, true);
            $loader->load();
            self::$loaded = new static($loader->allVarNames());
        }

        return self::$loaded;
    }

    /**
     * @return array
     */
    public static function wpConstants()
    {
        if (is_null(self::$all)) {
            self::$all = array_merge(
                self::$isBool,
                self::$isBoolOrInt,
                self::$isInt,
                self::$isMod,
                self::$isString
            );
        }

        return self::$all;
    }

    /**
     * @param array $vars
     */
    public function __construct(array $vars)
    {
        $this->vars = $this->process($vars);
    }

    /**
     * Return all vars have been set
     *
     * @return array
     */
    public function allVars()
    {
        return $this->vars;
    }

    /**
     * @param  array array
     * @return array
     */
    private function process(array $vars)
    {
        $values = array();
        $constants = self::wpConstants();
        foreach ($vars as $var) {

            $value = getenv($var);
            $values[$var] = $value;

            if (in_array($var, $constants, true)) {
                switch (true) {
                    case in_array($var, self::$isInt, true):
                        $values[$var] = (int)$value;
                        break;
                    case in_array($var, self::$isBool, true):
                        $values[$var] = (bool)filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    case in_array($var, self::$isBoolOrInt, true) :
                        if (is_numeric($value)) {
                            $values[$var] = (int)$value;
                            break;
                        }
                        $values[$var] = (bool)filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    case in_array($var, self::$isMod, true) :
                        $check = $this->checkMod($value);
                        is_null($check) or $values[$var] = $check;
                        break;
                }
            }
        }

        return $values;
    }

    /**
     * Checks that a value is a valid string representation of octal int file permission code.
     *
     * @param  string $mod
     * @return int|null
     */
    private function checkMod($mod)
    {
        if ($mod[0] === '0') {
            $mod = substr($mod, 1);
        }

        return strlen($mod) === 3 && str_replace(range(1, 7), '', $mod) === ''
            ? octdec($mod)
            : null;
    }

}