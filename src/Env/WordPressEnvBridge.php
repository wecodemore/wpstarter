<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Env;

use Symfony\Component\Dotenv\Dotenv;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Handle WordPress related environment variables using Symfony Env component.
 */
final class WordPressEnvBridge implements \ArrayAccess
{
    const CONSTANTS = [
        'ALLOW_UNFILTERED_UPLOADS' => Filters::FILTER_BOOL,
        'ALTERNATE_WP_CRON' => Filters::FILTER_BOOL,
        'AUTOMATIC_UPDATER_DISABLED' => Filters::FILTER_BOOL,
        'ALLOW_SUBDIRECTORY_INSTALL' => Filters::FILTER_BOOL,
        'COMPRESS_CSS' => Filters::FILTER_BOOL,
        'COMPRESS_SCRIPTS' => Filters::FILTER_BOOL,
        'CONCATENATE_SCRIPTS' => Filters::FILTER_BOOL,
        'CORE_UPGRADE_SKIP_NEW_BUNDLED' => Filters::FILTER_BOOL,
        'DIEONDBERROR' => Filters::FILTER_BOOL,
        'DISABLE_WP_CRON' => Filters::FILTER_BOOL,
        'DISALLOW_FILE_EDIT' => Filters::FILTER_BOOL,
        'DISALLOW_FILE_MODS' => Filters::FILTER_BOOL,
        'DISALLOW_UNFILTERED_HTML' => Filters::FILTER_BOOL,
        'DO_NOT_UPGRADE_GLOBAL_TABLES' => Filters::FILTER_BOOL,
        'ENFORCE_GZIP' => Filters::FILTER_BOOL,
        'IMAGE_EDIT_OVERWRITE' => Filters::FILTER_BOOL,
        'MEDIA_TRASH' => Filters::FILTER_BOOL,
        'MULTISITE' => Filters::FILTER_BOOL,
        'FORCE_SSL_LOGIN' => Filters::FILTER_BOOL,
        'FORCE_SSL_ADMIN' => Filters::FILTER_BOOL,
        'FTP_SSH' => Filters::FILTER_BOOL,
        'FTP_SSL' => Filters::FILTER_BOOL,
        'SAVEQUERIES' => Filters::FILTER_BOOL,
        'SCRIPT_DEBUG' => Filters::FILTER_BOOL,
        'SUBDOMAIN_INSTALL' => Filters::FILTER_BOOL,
        'WP_ALLOW_MULTISITE' => Filters::FILTER_BOOL,
        'WP_ALLOW_REPAIR' => Filters::FILTER_BOOL,
        'WP_AUTO_UPDATE_CORE' => Filters::FILTER_BOOL,
        'WP_HTTP_BLOCK_EXTERNAL' => Filters::FILTER_BOOL,
        'WP_CACHE' => Filters::FILTER_BOOL,
        'WP_DEBUG' => Filters::FILTER_BOOL,
        'WP_DEBUG_DISPLAY' => Filters::FILTER_BOOL,
        'WP_DEBUG_LOG' => Filters::FILTER_BOOL,
        'WPMU_ACCEL_REDIRECT' => Filters::FILTER_BOOL,
        'WPMU_SENDFILE' => Filters::FILTER_BOOL,
        'AUTOSAVE_INTERVAL' => Filters::FILTER_INT,
        'EMPTY_TRASH_DAYS' => Filters::FILTER_INT,
        'FS_TIMEOUT' => Filters::FILTER_INT,
        'FS_CONNECT_TIMEOUT' => Filters::FILTER_INT,
        'WP_CRON_LOCK_TIMEOUT' => Filters::FILTER_INT,
        'WP_MAIL_INTERVAL' => Filters::FILTER_INT,
        'SITE_ID_CURRENT_SITE' => Filters::FILTER_INT,
        'BLOG_ID_CURRENT_SITE' => Filters::FILTER_INT,
        'WP_PROXY_PORT' => Filters::FILTER_INT,
        'ABSPATH' => Filters::FILTER_STRING,
        'ADMIN_COOKIE_PATH' => Filters::FILTER_STRING,
        'AUTH_COOKIE' => Filters::FILTER_STRING,
        'BLOGUPLOADDIR' => Filters::FILTER_STRING,
        'COOKIEHASH' => Filters::FILTER_STRING,
        'COOKIEPATH' => Filters::FILTER_STRING,
        'COOKIE_DOMAIN' => Filters::FILTER_STRING,
        'CUSTOM_USER_META_TABLE' => Filters::FILTER_STRING,
        'CUSTOM_USER_TABLE' => Filters::FILTER_STRING,
        'DB_CHARSET' => Filters::FILTER_STRING,
        'DB_COLLATE' => Filters::FILTER_STRING,
        'DB_HOST' => Filters::FILTER_STRING,
        'DB_NAME' => Filters::FILTER_STRING,
        'DB_PASSWORD' => Filters::FILTER_STRING,
        'DB_TABLE_PREFIX' => Filters::FILTER_STRING,
        'DB_USER' => Filters::FILTER_STRING,
        'DOMAIN_CURRENT_SITE' => Filters::FILTER_STRING,
        'ERRORLOGFILE' => Filters::FILTER_STRING,
        'FS_METHOD' => Filters::FILTER_STRING,
        'FTP_BASE' => Filters::FILTER_STRING,
        'FTP_CONTENT_DIR' => Filters::FILTER_STRING,
        'FTP_HOST' => Filters::FILTER_STRING,
        'FTP_PASS' => Filters::FILTER_STRING,
        'FTP_PLUGIN_DIR' => Filters::FILTER_STRING,
        'FTP_PRIKEY' => Filters::FILTER_STRING,
        'FTP_PUBKEY' => Filters::FILTER_STRING,
        'FTP_USER' => Filters::FILTER_STRING,
        'LOGGED_IN_COOKIE' => Filters::FILTER_STRING,
        'MU_BASE' => Filters::FILTER_STRING,
        'NOBLOGREDIRECT' => Filters::FILTER_STRING,
        'PASS_COOKIE' => Filters::FILTER_STRING,
        'PATH_CURRENT_SITE' => Filters::FILTER_STRING,
        'PLUGINS_COOKIE_PATH' => Filters::FILTER_STRING,
        'SECURE_AUTH_COOKIE' => Filters::FILTER_STRING,
        'SITECOOKIEPATH' => Filters::FILTER_STRING,
        'TEST_COOKIE' => Filters::FILTER_STRING,
        'UPLOADBLOGSDIR' => Filters::FILTER_STRING,
        'UPLOADS' => Filters::FILTER_STRING,
        'USER_COOKIE' => Filters::FILTER_STRING,
        'WPLANG' => Filters::FILTER_STRING,
        'WPMU_PLUGIN_DIR' => Filters::FILTER_STRING,
        'WPMU_PLUGIN_URL' => Filters::FILTER_STRING,
        'WP_ACCESSIBLE_HOSTS' => Filters::FILTER_STRING,
        'WP_CONTENT_DIR' => Filters::FILTER_STRING,
        'WP_CONTENT_URL' => Filters::FILTER_STRING,
        'WP_DEFAULT_THEME' => Filters::FILTER_STRING,
        'WP_HOME' => Filters::FILTER_STRING,
        'WP_LANG_DIR' => Filters::FILTER_STRING,
        'WP_MAX_MEMORY_LIMIT' => Filters::FILTER_STRING,
        'WP_MEMORY_LIMIT' => Filters::FILTER_STRING,
        'WP_PLUGIN_DIR' => Filters::FILTER_STRING,
        'WP_PLUGIN_URL' => Filters::FILTER_STRING,
        'WP_PROXY_BYPASS_HOSTS' => Filters::FILTER_STRING,
        'WP_PROXY_HOST' => Filters::FILTER_STRING,
        'WP_PROXY_PASSWORD' => Filters::FILTER_STRING,
        'WP_PROXY_USERNAME' => Filters::FILTER_STRING,
        'WP_SITEURL' => Filters::FILTER_STRING,
        'WP_TEMP_DIR' => Filters::FILTER_STRING,
        'WP_POST_REVISIONS' => Filters::FILTER_INT_OR_BOOL,
        'FS_CHMOD_DIR' => Filters::FILTER_OCTAL_MOD,
        'FS_CHMOD_FILE' => Filters::FILTER_OCTAL_MOD,
    ];

    /**
     * @var WordPressEnvBridge[]
     */
    private static $loaded = [];

    /**
     * @var Filters
     */
    private $filters;

    /**
     * @var bool
     */
    private $fileLoadingSkipped = false;

    /**
     * @param Config $config
     * @param Paths $paths
     * @param Dotenv|null $dotEnv
     * @return WordPressEnvBridge
     */
    public static function loadFromConfig(
        Config $config,
        Paths $paths,
        Dotenv $dotEnv = null
    ): WordPressEnvBridge {

        $envDir = $config[Config::ENV_DIR]->unwrapOrFallback($paths->root());
        $envFile = $config[Config::ENV_FILE]->unwrapOrFallback('.env');

        return static::loadFile("{$envDir}/{$envFile}", $dotEnv);
    }

    /**
     * @param  string $path Environment file path
     * @param  string $file Environment file path relative to `$path`
     * @param Dotenv|null $dotEnv
     * @return \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public static function load(
        string $path = null,
        string $file = '.env',
        Dotenv $dotEnv = null
    ): WordPressEnvBridge {

        $path === null and $path = getcwd();

        return static::loadFile(rtrim($path, '\\/') . "/{$file}", $dotEnv);
    }

    /**
     * @param string $path
     * @param Dotenv|null $dotEnv
     * @return WordPressEnvBridge
     */
    private static function loadFile(string $path, Dotenv $dotEnv = null): WordPressEnvBridge
    {
        if (getenv('WPSTARTER_ENV_LOADED')) {
            self::$loaded['$'] = new static();
            self::$loaded['$']->fileLoadingSkipped = true;

            return self::$loaded['$'];
        }

        $path = realpath($path);
        if ($path && !empty(self::$loaded[$path])) {
            return self::$loaded[$path];
        }

        if (!$path || !is_file($path) || !is_readable($path)) {
            throw new \RuntimeException(
                'Please provide a .env file or ensure WPSTARTER_ENV_LOADED variable is set.'
            );
        }

        self::$loaded[$path] = new static();
        $dotEnv or $dotEnv = new Dotenv();
        $dotEnv->load($path);

        return self::$loaded[$path];
    }

    /**
     * Return all environment variables that have been set
     *
     * @return void
     */
    public function setupWordPress()
    {
        $names = array_keys(self::CONSTANTS);
        array_walk($names, [$this, 'defineWpConstant']);
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        // Unfortunately we can't type-declare `string` having to stick with ArrayAccess signature.
        $this->assertString($offset, __METHOD__);

        return
            array_key_exists($offset, $_ENV)
            || array_key_exists($offset, $_SERVER)
            || (getenv($offset) !== false);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        // Unfortunately we can't type-declare `string` having to stick with ArrayAccess signature.
        $this->assertString($offset, __METHOD__);

        $defined = defined($offset);

        if (!$this->offsetExists($offset) && !$defined) {
            return null;
        }

        if ($defined) {
            return constant($offset);
        }

        $value = $_ENV[$offset] ?? $_SERVER[$offset] ?? getenv($offset) ?: null;

        if (!array_key_exists($offset, self::CONSTANTS)) {
            return $value;
        }

        $this->filters or $this->filters = new Filters();

        return $this->filters->filter(self::CONSTANTS[$offset], $value);
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
     * Define WP constants from environment variables
     *
     * @param string $name
     */
    private function defineWpConstant(string $name)
    {
        if ($name === 'DB_TABLE_PREFIX') {
            $this->defineTablePrefix();

            return;
        }

        if (!defined($name)) {
            $value = $this->offsetGet($name);
            $value === null or define($name, $value);
        }
    }

    /**
     * DB table prefix is a global variable in WP, so it differs from other settings to be set
     * from environment variables because those are constants in WP.
     *
     * WP Starter makes up the `DB_TABLE_PREFIX` environment variable to set DB table prefix.
     */
    private function defineTablePrefix()
    {
        $value = $this->offsetGet('DB_TABLE_PREFIX') ?: '';

        if ($value || !isset($GLOBALS['table_prefix'])) {
            $value or $value = 'wp_';
            $GLOBALS['table_prefix'] = preg_replace('#[\W]#', '', (string)$value);
        }
    }

    /**
     * @param $value
     * @param string $method
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    private function assertString($value, string $method)
    {
        // phpcs:enable

        if (!is_string($value)) {
            throw new \TypeError(
                sprintf(
                    'Argument 1 passed to %s() must be of the type string, %s given.',
                    $method,
                    gettype($value)
                )
            );
        }
    }
}
