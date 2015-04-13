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
        'WP_POST_REVISIONS',
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

    /**
     * @var array
     */
    private static $isBoolOrInt = array('WP_POST_REVISIONS');

    /**
     * @var array
     */
    private static $isMod = array('FS_CHMOD_DIR', 'FS_CHMOD_FILE');

    /**
     * @inheritdoc
     */
    public static function load($path)
    {
        if (! self::$loaded) {
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
            self::$set[$normName] = $normValue;
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
     * @uses \WCM\WPStarter\Env::checkCollate()
     * @uses \WCM\WPStarter\Env::checkMod()
     */
    private static function normalise($name, $value)
    {
        list($normName, $normValue) = parent::normaliseEnvironmentVariable($name, $value);
        if (empty($normName) || is_null($normValue)) {
            return array(null, null);
        }
        if (in_array($normName, self::$isInt, true)) {
            $normValue = (int) filter_var($normValue, FILTER_VALIDATE_INT);
        } elseif (in_array($normName, self::$isBool, true)) {
            $normValue = (bool) filter_var($normValue, FILTER_VALIDATE_BOOLEAN);
        } elseif (in_array($normName, self::$isBoolOrInt, true)) {
            $filter = is_numeric($normValue) ? FILTER_VALIDATE_INT : FILTER_VALIDATE_BOOLEAN;
            $normValue = filter_var($normValue, $filter);
        } elseif (in_array($normName, self::$isMod, true)) {
            $normValue = self::checkMod($normValue);
        }

        return ! empty($normValue) ? array($normName, $normValue) : array($normName, null);
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
