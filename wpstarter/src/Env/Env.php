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

use Gea\Gea;

/**
 * Handle WordPress related environment variables using Gea library.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class Env implements \ArrayAccess
{
    /**
     * @var static
     */
    private static $loaded;

    /**
     * @var array
     */
    private static $isBool = [
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
    ];

    /**
     * @var array
     */
    private static $isInt = [
        'AUTOSAVE_INTERVAL',
        'EMPTY_TRASH_DAYS',
        'FS_TIMEOUT',
        'FS_CONNECT_TIMEOUT',
        'WP_CRON_LOCK_TIMEOUT',
        'WP_MAIL_INTERVAL',
        'SITE_ID_CURRENT_SITE',
        'BLOG_ID_CURRENT_SITE',
        'WP_PROXY_PORT',
    ];

    private static $isString = [
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
    ];

    /**
     * @var array
     */
    private static $isBoolOrInt = [
        'WP_POST_REVISIONS',
    ];

    /**
     * @var array
     */
    private static $isMod = [
        'FS_CHMOD_DIR',
        'FS_CHMOD_FILE',
    ];

    /**
     * @var array
     */
    private static $all;

    /**
     * @var \Gea\Gea
     */
    private $gea;

    /**
     * @var array
     */
    private $names;

    /**
     * @param  string $path Environment file path
     * @param  string $file Environment file path relative to `$path`
     * @return \WCM\WPStarter\Env\Env
     */
    public static function load($path, $file = '.env')
    {
        if (! is_null(self::$loaded)) {
            return self::$loaded;
        }

        is_string($file) or $file = '.env';
        $path = is_string($path) && is_string($file)
            ? realpath(rtrim($path, '\\/').'/'.ltrim($file, '\\/'))
            : '';

        $loadFile = $path && is_file($path) && is_readable($path);
        if (! $loadFile && getenv('WORDPRESS_ENV') === false) {
            die('Please provide a .env file or ensure WORDPRESS_ENV variable is set.');
        }

        $gea = $loadFile
            ? Gea::instance($path, $file, Gea::READ_ONLY | Gea::VAR_NAMES_HOLD, new Accessor())
            : Gea::noLoaderInstance(Gea::READ_ONLY | Gea::VAR_NAMES_HOLD, new Accessor());

        $gea->addFilter(['DB_NAME', 'DB_USER', 'DB_PASSWORD'], 'required');
        $gea->addFilter(self::$isBool, 'bool');
        $gea->addFilter(self::$isInt, 'int');
        $gea->addFilter(self::$isBoolOrInt, ['callback' => [self::boolOrIntFilter()]]);
        $gea->addFilter(self::$isMod, ['callback' => [self::modFilter()]]);
        $gea->load();
        self::$loaded = new static($gea);

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
     * @return \Closure
     */
    private static function boolOrIntFilter()
    {
        return function ($value) {
            (is_string($value) && $value[0] === '0') and $value = substr($value, 1);

            return strlen($value) === 3 && str_replace(range(1, 7), '', $value) === ''
                ? octdec($value)
                : null;
        };
    }

    /**
     * @return \Closure
     */
    private static function modFilter()
    {
        return function ($value) {
            (is_string($value) && $value[0] === '0') and $value = substr($value, 1);

            return strlen($value) === 3 && str_replace(range(1, 7), '', $value) === ''
                ? octdec($value)
                : null;
        };
    }

    /**
     * @param \Gea\Gea $gea
     */
    public function __construct(Gea $gea)
    {
        $this->gea = $gea;
    }

    /**
     * Return all vars have been set
     *
     * @return array
     */
    public function allWpVars()
    {
        if (is_array($this->names)) {
            return $this->names;
        }

        self::wpConstants();

        $loaded = $this->gea->varNames();
        $this->names = $loaded
            ? array_intersect($loaded, self::$all)
            : array_filter(self::$all, [$this->gea, 'offsetExists']);

        return $this->names;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->gea->offsetExists($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->gea->offsetGet($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->gea->offsetSet($offset, $value);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this->gea->offsetUnset($offset);
    }
}
