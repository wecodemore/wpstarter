<?php

/**
 * This file is generated by WP Starter package, and contains base configuration of the WordPress.
 *
 * All the configuration constants used by WordPress are set via environment variables.
 * Default settings are provided in this file for most common settings, however database settings
 * are required, you can get them from your web host.
 */

use WeCodeMore\WpStarter\Env\WordPressEnvBridge;

DEBUG_INFO_INIT: {
    $debugInfo = [];
} #@@/DEBUG_INFO_INIT

ABSPATH: {
    /** Absolute path to the WordPress directory. */
    defined('ABSPATH') or define('ABSPATH', realpath(__DIR__ . '{{{WP_INSTALL_PATH}}}') . '/');

    /**
     * Load plugin.php early, so we can call hooks from here on.
     * E.g. in Composer-autoloaded "files".
     */
    require_once ABSPATH . 'wp-includes/plugin.php';
} #@@/ABSPATH

AUTOLOAD: {
    /** Composer autoload. */
    require_once realpath(__DIR__ . '{{{AUTOLOAD_PATH}}}');

    define('WPSTARTER_PATH', realpath(__DIR__ . '{{{WPSTARTER_PATH}}}'));
    define('WPSTARTER_ENV_PATH', realpath(__DIR__ . '{{{ENV_REL_PATH}}}'));

    $debugInfo['autoload-path'] = [
        'label' => 'Autoload path',
        'value' => realpath(__DIR__ . '{{{AUTOLOAD_PATH}}}'),
        'debug' => realpath(__DIR__ . '{{{AUTOLOAD_PATH}}}')
    ];
    $debugInfo['base-path'] = [
        'label' => 'Project root path',
        'value' => WPSTARTER_PATH,
        'debug' => WPSTARTER_PATH,
    ];
    $debugInfo['env-path'] = [
        'label' => 'Environment path',
        'value' => WPSTARTER_ENV_PATH,
        'debug' => WPSTARTER_ENV_PATH,
    ];
} #@@/AUTOLOAD

WPS_GETENV_FUNCTION: {
    function wpstarter_getenv(?string $key) {
        static $env;
        if ($env && ($key === null)) {
            throw new TypeError('wpstarter_env(): Argument #1 ($key) must be of type string, null given.');
        }
        if ($key === '') {
            throw new InvalidArgumentException('wpstarter_env(): Argument #1 ($key) must be a non-empty string.');
        }
        if (!$env) {
            $envCacheEnabled = filter_var('{{{CACHE_ENV}}}', FILTER_VALIDATE_BOOLEAN);
            $envCacheFile = WPSTARTER_ENV_PATH . WordPressEnvBridge::CACHE_DUMP_FILE;
            $env = $envCacheEnabled
                ? WordPressEnvBridge::buildFromCacheDump($envCacheFile)
                : new WordPressEnvBridge();
        }
        return ($key === null) ? $env : $env->read($key);
    }
    $envLoader = wpstarter_getenv(null);
} #@@/WPS_GETENV_FUNCTION

ENV_VARIABLES: {
    /**
     * Environment variables will be loaded from file, unless `WPSTARTER_ENV_LOADED` env var is
     * already setup e.g. via webserver configuration.
     * In that case all environment variables are assumed to be set.
     * Environment variables that are set in the *real* environment (e.g. via webserver) will not be
     * overridden from file, even if `WPSTARTER_ENV_LOADED` is not set.
     */
    $envIsCached = $envLoader->hasCachedValues();
    if (!$envIsCached) {
        $envLoader->load('{{{ENV_FILE_NAME}}}', WPSTARTER_ENV_PATH);
        $envType = $envLoader->determineEnvType();
        if ($envType !== 'example') {
            $envLoader->loadAppended("{{{ENV_FILE_NAME}}}.{$envType}", WPSTARTER_ENV_PATH);
        }
    }
    /**
     * Define all WordPress constants from environment variables.
     *
     * Core wp_get_environment_type() only supports a pre-defined list of environments types.
     * WP Starter tries to map different environments to values supported by core, for example
     * "dev" (or "develop", or even "develop-1") will be mapped to "development" accepted by WP.
     * In that case, `wp_get_environment_type()` will return "development", but `WP_ENV` will still
     * be "dev" (or "develop", or "develop-1").
     */
    $envIsCached ? $envLoader->setupEnvConstants() : $envLoader->setupConstants();
    isset($envType) or $envType = $envLoader->determineEnvType();
    defined('WP_ENVIRONMENT_TYPE') or define('WP_ENVIRONMENT_TYPE', 'production');

    $envCacheFile = realpath(WPSTARTER_ENV_PATH . WordPressEnvBridge::CACHE_DUMP_FILE);
    $debugInfo['env-cache-file'] = [
        'label' => 'Env cache file',
        'value' => $envCacheFile ?: 'None',
        'debug' => $envCacheFile,
    ];
    $envCacheEnabled = filter_var('{{{CACHE_ENV}}}', FILTER_VALIDATE_BOOLEAN);
    $debugInfo['env-cache-enabled'] = [
        'label' => 'Env cache enabled',
        'value' => $envCacheEnabled ? 'Yes' : 'No',
        'debug' => $envCacheEnabled,
    ];
    $debugInfo['cached-env'] = [
        'label' => 'Is env loaded from cache',
        'value' => $envIsCached ? 'Yes' : 'No',
        'debug' => $envIsCached,
    ];
    $debugInfo['env-type'] = [
        'label' => 'Env type',
        'value' => $envType,
        'debug' => $envType,
    ];
    $debugInfo['wp-env-type'] = [
        'label' => 'WordPress env type',
        'value' => WP_ENVIRONMENT_TYPE,
        'debug' => WP_ENVIRONMENT_TYPE,
    ];

    unset($envCacheEnabled, $envIsCached, $envCacheFile);

    $phpEnvFilePath = realpath(__DIR__ . "{{{ENV_BOOTSTRAP_DIR}}}/{$envType}.php");
    $hasPhpEnvFile = $phpEnvFilePath && file_exists($phpEnvFilePath) && is_readable($phpEnvFilePath);
    if ($hasPhpEnvFile) {
        require_once $phpEnvFilePath;
    }
    $debugInfo['env-php-file'] = [
        'label' => 'Env-specific PHP file',
        'value' => $hasPhpEnvFile ? $phpEnvFilePath : 'None',
        'debug' => $hasPhpEnvFile ? $phpEnvFilePath : '',
    ];
    unset($phpEnvFilePath, $hasPhpEnvFile);
} #@@/ENV_VARIABLES

KEYS: {
    /**#@+
     * Authentication Unique Keys and Salts.
     */
    defined('AUTH_KEY') or define('AUTH_KEY', '{{{AUTH_KEY}}}');
    defined('SECURE_AUTH_KEY') or define('SECURE_AUTH_KEY', '{{{SECURE_AUTH_KEY}}}');
    defined('LOGGED_IN_KEY') or define('LOGGED_IN_KEY', '{{{LOGGED_IN_KEY}}}');
    defined('NONCE_KEY') or define('NONCE_KEY', '{{{NONCE_KEY}}}');
    defined('AUTH_SALT') or define('AUTH_SALT', '{{{AUTH_SALT}}}');
    defined('SECURE_AUTH_SALT') or define('SECURE_AUTH_SALT', '{{{SECURE_AUTH_SALT}}}');
    defined('LOGGED_IN_SALT') or define('LOGGED_IN_SALT', '{{{LOGGED_IN_SALT}}}');
    defined('NONCE_SALT') or define('NONCE_SALT', '{{{NONCE_SALT}}}');
    /**#@-*/
} #@@/KEYS

DB_SETUP : {
    /** Set optional database settings if not already set. */
    defined('DB_HOST') or define('DB_HOST', 'localhost');
    defined('DB_CHARSET') or define('DB_CHARSET', 'utf8');
    defined('DB_COLLATE') or define('DB_COLLATE', '');

    /**
     * WordPress Database Table prefix.
     */
    global $table_prefix;
    $table_prefix = $envLoader->read('DB_TABLE_PREFIX') ?: 'wp_';
} #@@/DB_SETUP

EARLY_HOOKS : {
    /**
     * Load early hooks file if any.
     * Early hooks file allows adding hooks that are triggered before plugins are loaded, e.g.
     * "enable_loading_advanced_cache_dropin" or to just-in-time define configuration constants.
     */
    $earlyHookFile = '{{{EARLY_HOOKS_FILE}}}'
        && file_exists(__DIR__ . '{{{EARLY_HOOKS_FILE}}}')
        && is_readable(__DIR__ . '{{{EARLY_HOOKS_FILE}}}');
    if ($earlyHookFile) {
        require_once __DIR__ . '{{{EARLY_HOOKS_FILE}}}';
    }
    $debugInfo['early-hooks-file'] = [
        'label' => 'Early hooks file',
        'value' => $earlyHookFile ? __DIR__ . '{{{EARLY_HOOKS_FILE}}}' : 'None',
        'debug' => $earlyHookFile ? __DIR__ . '{{{EARLY_HOOKS_FILE}}}' : '',
    ];
    unset($earlyHookFile);
} #@@/EARLY_HOOKS

DEFAULT_ENV : {
    /** Environment-aware settings. Be creative, but avoid having sensitive settings here. */
    switch (WP_ENVIRONMENT_TYPE) {
        case 'local':
            defined('WP_LOCAL_DEV') or define('WP_LOCAL_DEV', true);
            defined('WP_DEVELOPMENT_MODE') or define('WP_DEVELOPMENT_MODE', 'all');
        case 'development':
            defined('WP_DEBUG') or define('WP_DEBUG', true);
            defined('WP_DEBUG_DISPLAY') or define('WP_DEBUG_DISPLAY', true);
            defined('WP_DEBUG_LOG') or define('WP_DEBUG_LOG', false);
            defined('SAVEQUERIES') or define('SAVEQUERIES', true);
            defined('SCRIPT_DEBUG') or define('SCRIPT_DEBUG', true);
            defined('WP_DISABLE_FATAL_ERROR_HANDLER') or define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
            break;
        case 'staging':
            defined('WP_DEBUG') or define('WP_DEBUG', true);
            defined('WP_DEBUG_DISPLAY') or define('WP_DEBUG_DISPLAY', false);
            defined('WP_DEBUG_LOG') or define('WP_DEBUG_LOG', true);
            defined('SAVEQUERIES') or define('SAVEQUERIES', false);
            defined('SCRIPT_DEBUG') or define('SCRIPT_DEBUG', true);
            break;
        case 'production':
        default:
            defined('WP_DEBUG') or define('WP_DEBUG', false);
            defined('WP_DEBUG_DISPLAY') or define('WP_DEBUG_DISPLAY', false);
            defined('WP_DEBUG_LOG') or define('WP_DEBUG_LOG', false);
            defined('SAVEQUERIES') or define('SAVEQUERIES', false);
            defined('SCRIPT_DEBUG') or define('SCRIPT_DEBUG', false);
            break;
    }
} #@@/DEFAULT_ENV

SSL_FIX : {
    $doSslFix = $envLoader->read('WP_FORCE_SSL_FORWARDED_PROTO')
        && array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER)
        && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
    $doSslFix and $_SERVER['HTTPS'] = 'on';
    $debugInfo['ssl_fix'] = [
        'label' => 'SSL fix for load balancers',
        'value' => $doSslFix ? 'Yes' : 'No',
        'debug' => $doSslFix,
    ];
    unset($doSslFix);
} #@@/SSL_FIX

URL_CONSTANTS : {
    // Defining WP_HOME is highly suggested. We do our best here, but this will never be 100% fine.
    if (!defined('WP_HOME')) {
        $port = is_numeric($_SERVER['SERVER_PORT'] ?? '') ? (int)$_SERVER['SERVER_PORT'] : 0;
        $scheme = isset($_SERVER['HTTPS'])
            ? (filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN) ? 'https' : 'http')
            : ($port === 443 ? 'https' : 'http');
        $home = "{$scheme}://";
        $home .= $_SERVER['SERVER_NAME'] ?? 'localhost';
        $ports = ['https' => 443, 'http' => 80];
        (($port > 0) && ($port !== $ports[$scheme])) and $home .= sprintf(':%d', $port);
        define('WP_HOME', $home);
        unset($port, $scheme, $home, $ports);
    }

    /** Set WordPress other URL / path constants not set via environment variables. */
    defined('WP_SITEURL') or define('WP_SITEURL', rtrim(WP_HOME, '/') . '/{{{WP_SITEURL_RELATIVE}}}');
    defined('WP_CONTENT_DIR') or define('WP_CONTENT_DIR', realpath(__DIR__ . '{{{WP_CONTENT_PATH}}}'));
    defined('WP_CONTENT_URL') or define('WP_CONTENT_URL', rtrim(WP_HOME, '/') . '/{{{WP_CONTENT_URL_RELATIVE}}}');
} #@@/URL_CONSTANTS

THEMES_REGISTER : {
    /** Register default themes inside WordPress package wp-content folder. */
    $registerThemeFolder = filter_var('{{{REGISTER_THEME_DIR}}}', FILTER_VALIDATE_BOOLEAN);
    $registerThemeFolder and add_action('plugins_loaded', static function (): void {
        register_theme_directory(ABSPATH . 'wp-content/themes');
    });
    $debugInfo['register-core-themes'] = [
        'label' => 'Register core themes folder',
        'value' => $registerThemeFolder ? 'Yes' : 'No',
        'debug' => $registerThemeFolder,
    ];
    unset($registerThemeFolder);
} #@@/THEMES_REGISTER

ADMIN_COLOR : {
    /** Allow changing admin color scheme. Useful to distinguish environments in the dashboard. */
    add_filter(
        'get_user_option_admin_color',
        static function ($color) use ($envLoader) {
            return $envLoader->read('WP_ADMIN_COLOR') ?: $color;
        },
        999
    );
} #@@/ADMIN_COLOR

ENV_CACHE : {
    /** On shutdown, we dump environment so that on subsequent requests we can load it faster */
    if ('{{{CACHE_ENV}}}' && $envLoader->isWpSetup()) {
        register_shutdown_function(
            static function () use ($envLoader, $envType) {
                $isLocal = $envType === 'local';
                $isDevMode = defined('WP_DEVELOPMENT_MODE') && WP_DEVELOPMENT_MODE;
                if (!apply_filters('wpstarter.skip-cache-env', $isLocal || $isDevMode, $envType)) {
                    $envLoader->dumpCached(WPSTARTER_ENV_PATH . WordPressEnvBridge::CACHE_DUMP_FILE);
                }
            }
        );
    }
} #@@/ENV_CACHE

DEBUG_INFO : {
    add_filter(
        'debug_information',
        static function ($info) use ($debugInfo): array {
            is_array($info) or $info = [];
            $info['wp-starter'] = ['label' => 'WP Starter', 'fields' => $debugInfo];

            return $info;
        },
        30
    );
} #@@/DEBUG_INFO

BEFORE_BOOTSTRAP : {
    /** A pre-defined section to extend configuration. */
} #@@/BEFORE_BOOTSTRAP

CLEAN_UP : {
    unset($debugInfo, $envType, $envLoader);
} #@@/CLEAN_UP

WP_CLI_HACK : {
    if (defined('WP_STARTER_WP_CONFIG_PATH') && defined('WP_CLI') && \WP_CLI) {
        return;
    }
} #@@/WP_CLI_HACK

###################################################################################################
#  I've seen things you people wouldn't believe. Attack ships on fire off the shoulder of Orion.  #
#                 I watched C-beams glitter in the dark near the Tannhäuser Gate.                 #
#            All those moments will be lost in time, like tears in rain. Time to die.             #
###################################################################################################

/* That's all, stop editing! Happy blogging. */

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
