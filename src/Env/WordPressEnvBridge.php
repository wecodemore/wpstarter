<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Env;

use Symfony\Component\Dotenv\Dotenv;

/**
 * Handle WordPress related environment variables using Symfony Env component.
 */
final class WordPressEnvBridge implements \ArrayAccess
{
    const CONSTANTS = [
        'ALLOW_UNFILTERED_UPLOADS' => 'bool',
        'ALTERNATE_WP_CRON' => 'bool',
        'AUTOMATIC_UPDATER_DISABLED' => 'bool',
        'ALLOW_SUBDIRECTORY_INSTALL' => 'bool',
        'COMPRESS_CSS' => 'bool',
        'COMPRESS_SCRIPTS' => 'bool',
        'CONCATENATE_SCRIPTS' => 'bool',
        'CORE_UPGRADE_SKIP_NEW_BUNDLED' => 'bool',
        'DIEONDBERROR' => 'bool',
        'DISABLE_WP_CRON' => 'bool',
        'DISALLOW_FILE_EDIT' => 'bool',
        'DISALLOW_FILE_MODS' => 'bool',
        'DISALLOW_UNFILTERED_HTML' => 'bool',
        'DO_NOT_UPGRADE_GLOBAL_TABLES' => 'bool',
        'ENFORCE_GZIP' => 'bool',
        'IMAGE_EDIT_OVERWRITE' => 'bool',
        'MEDIA_TRASH' => 'bool',
        'MULTISITE' => 'bool',
        'FORCE_SSL_LOGIN' => 'bool',
        'FORCE_SSL_ADMIN' => 'bool',
        'FTP_SSH' => 'bool',
        'FTP_SSL' => 'bool',
        'SAVEQUERIES' => 'bool',
        'SCRIPT_DEBUG' => 'bool',
        'SUBDOMAIN_INSTALL' => 'bool',
        'WP_ALLOW_MULTISITE' => 'bool',
        'WP_ALLOW_REPAIR' => 'bool',
        'WP_AUTO_UPDATE_CORE' => 'bool',
        'WP_HTTP_BLOCK_EXTERNAL' => 'bool',
        'WP_CACHE' => 'bool',
        'WP_DEBUG' => 'bool',
        'WP_DEBUG_DISPLAY' => 'bool',
        'WP_DEBUG_LOG' => 'bool',
        'WPMU_ACCEL_REDIRECT' => 'bool',
        'WPMU_SENDFILE' => 'bool',
        'AUTOSAVE_INTERVAL' => 'int',
        'EMPTY_TRASH_DAYS' => 'int',
        'FS_TIMEOUT' => 'int',
        'FS_CONNECT_TIMEOUT' => 'int',
        'WP_CRON_LOCK_TIMEOUT' => 'int',
        'WP_MAIL_INTERVAL' => 'int',
        'SITE_ID_CURRENT_SITE' => 'int',
        'BLOG_ID_CURRENT_SITE' => 'int',
        'WP_PROXY_PORT' => 'int',
        'ABSPATH' => 'string',
        'ADMIN_COOKIE_PATH' => 'string',
        'AUTH_COOKIE' => 'string',
        'BLOGUPLOADDIR' => 'string',
        'COOKIEHASH' => 'string',
        'COOKIEPATH' => 'string',
        'COOKIE_DOMAIN' => 'string',
        'CUSTOM_USER_META_TABLE' => 'string',
        'CUSTOM_USER_TABLE' => 'string',
        'DB_CHARSET' => 'string',
        'DB_COLLATE' => 'string',
        'DB_HOST' => 'string',
        'DB_NAME' => 'string',
        'DB_PASSWORD' => 'string',
        'DB_TABLE_PREFIX' => 'string',
        'DB_USER' => 'string',
        'DOMAIN_CURRENT_SITE' => 'string',
        'ERRORLOGFILE' => 'string',
        'FS_METHOD' => 'string',
        'FTP_BASE' => 'string',
        'FTP_CONTENT_DIR' => 'string',
        'FTP_HOST' => 'string',
        'FTP_PASS' => 'string',
        'FTP_PLUGIN_DIR' => 'string',
        'FTP_PRIKEY' => 'string',
        'FTP_PUBKEY' => 'string',
        'FTP_USER' => 'string',
        'LOGGED_IN_COOKIE' => 'string',
        'MU_BASE' => 'string',
        'NOBLOGREDIRECT' => 'string',
        'PASS_COOKIE' => 'string',
        'PATH_CURRENT_SITE' => 'string',
        'PLUGINS_COOKIE_PATH' => 'string',
        'SECURE_AUTH_COOKIE' => 'string',
        'SITECOOKIEPATH' => 'string',
        'TEST_COOKIE' => 'string',
        'UPLOADBLOGSDIR' => 'string',
        'UPLOADS' => 'string',
        'USER_COOKIE' => 'string',
        'WPLANG' => 'string',
        'WPMU_PLUGIN_DIR' => 'string',
        'WPMU_PLUGIN_URL' => 'string',
        'WP_ACCESSIBLE_HOSTS' => 'string',
        'WP_CONTENT_DIR' => 'string',
        'WP_CONTENT_URL' => 'string',
        'WP_DEFAULT_THEME' => 'string',
        'WP_HOME' => 'string',
        'WP_LANG_DIR' => 'string',
        'WP_MAX_MEMORY_LIMIT' => 'string',
        'WP_MEMORY_LIMIT' => 'string',
        'WP_PLUGIN_DIR' => 'string',
        'WP_PLUGIN_URL' => 'string',
        'WP_PROXY_BYPASS_HOSTS' => 'string',
        'WP_PROXY_HOST' => 'string',
        'WP_PROXY_PASSWORD' => 'string',
        'WP_PROXY_USERNAME' => 'string',
        'WP_SITEURL' => 'string',
        'WP_TEMP_DIR' => 'string',
        'WP_POST_REVISIONS' => 'int|bool',
        'FS_CHMOD_DIR' => 'mod',
        'FS_CHMOD_FILE' => 'mod',
    ];

    /**
     * @var WordPressEnvBridge
     */
    private static $loaded;

    /**
     * @var null|Dotenv
     */
    private $dotenv;

    /**
     * @var string
     */
    private $dotEnvFile;

    /**
     * @var bool
     */
    private $unloaded = false;

    /**
     * @param  string $path Environment file path
     * @param  string $file Environment file path relative to `$path`
     * @return \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public static function load(string $path, string $file = '.env'): WordPressEnvBridge
    {
        if (self::$loaded) {
            return self::$loaded;
        }

        if (getenv('WPSTARTER_ENV_LOADED') !== false) {
            self::$loaded = new static();
            self::$loaded->unloaded = true;

            return self::$loaded;
        }

        $path = realpath(rtrim($path, '\\/') . "/{$file}");

        if (!$path || !is_file($path) || !is_readable($path)) {
            throw new \RuntimeException(
                'Please provide a .env file or ensure WPSTARTER_ENV_LOADED variable is set.'
            );
        }

        self::$loaded = new static($path);

        return self::$loaded;
    }

    /**
     * @param string $dotEnvFile
     * @param Dotenv|null $dotenv
     */
    public function __construct(string $dotEnvFile = null, Dotenv $dotenv = null)
    {
        $this->dotenv = $dotenv;
        $this->dotEnvFile = $dotEnvFile;
    }

    /**
     * Return all environment variables that have been set
     *
     * @return void
     */
    public function setupWordPress()
    {
        if ($this->dotEnvFile) {
            $this->dotenv or $this->dotenv = new Dotenv();
            $this->dotenv->load($this->dotEnvFile);
        }

        $names = array_keys(self::CONSTANTS);
        array_walk($names, [$this, 'defineWpConstant']);
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return
            array_key_exists($offset, $_ENV)
            || array_key_exists($offset, $_SERVER)
            || getenv($offset) !== false;
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $defined = $this->unloaded ? defined($offset) : false;

        if (!$this->offsetExists($offset) && !$defined) {
            return null;
        }

        if ($defined) {
            return constant($offset);
        }

        $value = $_ENV[$offset] ?? $_SERVER[$offset] ?? getenv($offset);

        if (!array_key_exists($offset, self::CONSTANTS)) {
            return $value;
        }

        return $this->filterValue($value, self::CONSTANTS[$offset]);
    }

    /**
     * Disabled. Class is read-only.
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException(__CLASS__ . ' is read only.');
    }

    /**
     * Disabled. Class is read-only.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException(__CLASS__ . ' is read only.');
    }

    /**
     * Define WP contants from environment variables
     *
     * @param string $name
     */
    private function defineWpConstant(string $name)
    {
        if ($name === 'DB_TABLE_PREFIX') {
            $this->defineTablePrexix();

            return;
        }

        if (!defined($name)) {
            $value = $this->offsetGet($name);
            $value === null or define($name, $value);
        }
    }

    /**
     * Sets the table prefix global from DB_TABLE_PREFIX env var.
     */
    private function defineTablePrexix()
    {
        $value = $this->offsetGet('DB_TABLE_PREFIX') ?: '';

        if ($value || !isset($GLOBALS['table_prefix'])) {
            $value or $value = 'wp_';
            $GLOBALS['table_prefix'] = preg_replace('#[\W]#', '', (string)$value);
        }
    }

    /**
     * @param string $value
     * @param string $type
     * @return mixed
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    private function filterValue(string $value, string $type)
    {
        // phpcs:enable

        switch ($type) {
            case 'bool':
                return (bool)filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'int':
                return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            case 'string':
                return (int)filter_var($value, FILTER_SANITIZE_STRING);
            case 'int|bool':
                return is_numeric($value)
                    ? (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT)
                    : (bool)filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'mod':
                (is_string($value) && $value[0] === '0') and $value = substr($value, 1);
                return strlen($value) === 3 && str_replace(range(1, 7), '', $value) === ''
                    ? octdec($value)
                    : null;
        }

        return $value;
    }
}
