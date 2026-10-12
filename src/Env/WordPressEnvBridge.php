<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Env;

use Symfony\Component\Dotenv\Dotenv;

/**
 * Handle WordPress related environment variables using Symfony Env component.
 */
class WordPressEnvBridge
{
    public const WP_CONSTANTS = [
        'ABSPATH' => Filters::FILTER_STRING,
        'ADMIN_COOKIE_PATH' => Filters::FILTER_STRING,
        'ALLOW_SUBDIRECTORY_INSTALL' => Filters::FILTER_BOOL,
        'ALLOW_UNFILTERED_UPLOADS' => Filters::FILTER_BOOL,
        'ALTERNATE_WP_CRON' => Filters::FILTER_BOOL,
        'AUTH_COOKIE' => Filters::FILTER_STRING,
        'AUTH_KEY' => Filters::FILTER_RAW_STRING,
        'AUTH_SALT' => Filters::FILTER_RAW_STRING,
        'AUTOMATIC_UPDATER_DISABLED' => Filters::FILTER_BOOL,
        'AUTOSAVE_INTERVAL' => Filters::FILTER_INT,

        'BACKGROUND_COLOR' => Filters::FILTER_STRING,
        'BACKGROUND_IMAGE' => Filters::FILTER_STRING,
        'BLOGUPLOADDIR' => Filters::FILTER_STRING,
        'BLOG_ID_CURRENT_SITE' => Filters::FILTER_INT,

        'COMPRESS_CSS' => Filters::FILTER_BOOL,
        'COMPRESS_SCRIPTS' => Filters::FILTER_BOOL,
        'CONCATENATE_SCRIPTS' => Filters::FILTER_BOOL,
        'COOKIEHASH' => Filters::FILTER_STRING,
        'COOKIEPATH' => Filters::FILTER_STRING,
        'COOKIE_DOMAIN' => Filters::FILTER_STRING,
        'CORE_UPGRADE_SKIP_NEW_BUNDLED' => Filters::FILTER_BOOL,
        'CUSTOM_TAGS' => Filters::FILTER_STRING,
        'CUSTOM_USER_META_TABLE' => Filters::FILTER_STRING,
        'CUSTOM_USER_TABLE' => Filters::FILTER_STRING,

        'DB_CHARSET' => Filters::FILTER_STRING,
        'DB_COLLATE' => Filters::FILTER_STRING,
        'DB_HOST' => Filters::FILTER_STRING,
        'DB_NAME' => Filters::FILTER_STRING,
        'DB_PASSWORD' => Filters::FILTER_RAW_STRING,
        'DB_USER' => Filters::FILTER_STRING,
        'DIEONDBERROR' => Filters::FILTER_BOOL,
        'DISABLE_WP_CRON' => Filters::FILTER_BOOL,
        'DISALLOW_FILE_EDIT' => Filters::FILTER_BOOL,
        'DISALLOW_FILE_MODS' => Filters::FILTER_BOOL,
        'DISALLOW_UNFILTERED_HTML' => Filters::FILTER_BOOL,
        'DOMAIN_CURRENT_SITE' => Filters::FILTER_STRING,
        'DO_NOT_UPGRADE_GLOBAL_TABLES' => Filters::FILTER_BOOL,

        'EDIT_ANY_USER' => Filters::FILTER_BOOL,
        'EMPTY_TRASH_DAYS' => Filters::FILTER_INT,
        'ENFORCE_GZIP' => Filters::FILTER_BOOL,
        'ERRORLOGFILE' => Filters::FILTER_STRING,

        'FORCE_SSL_ADMIN' => Filters::FILTER_BOOL,
        'FORCE_SSL_LOGIN' => Filters::FILTER_BOOL,
        'FS_CHMOD_DIR' => Filters::FILTER_OCTAL_MOD,
        'FS_CHMOD_FILE' => Filters::FILTER_OCTAL_MOD,
        'FS_CONNECT_TIMEOUT' => Filters::FILTER_INT,
        'FS_METHOD' => Filters::FILTER_STRING,
        'FS_TIMEOUT' => Filters::FILTER_INT,
        'FTP_ASCII' => Filters::FILTER_INT,
        'FTP_AUTOASCII' => Filters::FILTER_INT,
        'FTP_BASE' => Filters::FILTER_STRING,
        'FTP_BINARY' => Filters::FILTER_INT,
        'FTP_CONTENT_DIR' => Filters::FILTER_STRING,
        'FTP_FORCE' => Filters::FILTER_BOOL,
        'FTP_HOST' => Filters::FILTER_STRING,
        'FTP_LANG_DIR' => Filters::FILTER_STRING,
        'FTP_PASS' => Filters::FILTER_RAW_STRING,
        'FTP_PLUGIN_DIR' => Filters::FILTER_STRING,
        'FTP_PRIKEY' => Filters::FILTER_STRING,
        'FTP_PUBKEY' => Filters::FILTER_STRING,
        'FTP_SSH' => Filters::FILTER_BOOL,
        'FTP_SSL' => Filters::FILTER_BOOL,
        'FTP_USER' => Filters::FILTER_STRING,

        'HEADER_IMAGE' => Filters::FILTER_STRING,
        'HEADER_IMAGE_WIDTH' => Filters::FILTER_INT,
        'HEADER_IMAGE_HEIGHT' => Filters::FILTER_INT,
        'HEADER_TEXTCOLOR' => Filters::FILTER_STRING,

        'IMAGE_EDIT_OVERWRITE' => Filters::FILTER_BOOL,

        'LANGDIR' => Filters::FILTER_STRING,
        'LOGGED_IN_COOKIE' => Filters::FILTER_STRING,
        'LOGGED_IN_KEY' => Filters::FILTER_RAW_STRING,
        'LOGGED_IN_SALT' => Filters::FILTER_RAW_STRING,

        'MEDIA_TRASH' => Filters::FILTER_BOOL,
        'MULTISITE' => Filters::FILTER_BOOL,
        'MUPLUGINDIR' => Filters::FILTER_STRING,
        'MU_BASE' => Filters::FILTER_STRING,
        'MYSQL_CLIENT_FLAGS' => Filters::FILTER_INT,
        'MYSQL_NEW_LINK' => Filters::FILTER_BOOL,

        'NOBLOGREDIRECT' => Filters::FILTER_STRING,
        'NONCE_KEY' => Filters::FILTER_RAW_STRING,
        'NONCE_SALT' => Filters::FILTER_RAW_STRING,
        'NO_HEADER_TEXT' => Filters::FILTER_STRING,

        'PASS_COOKIE' => Filters::FILTER_STRING,
        'PATH_CURRENT_SITE' => Filters::FILTER_STRING,
        'PCLZIP_ERROR_EXTERNAL' => Filters::FILTER_INT,
        'PCLZIP_READ_BLOCK_SIZE' => Filters::FILTER_INT,
        'PCLZIP_SEPARATOR' => Filters::FILTER_STRING,
        'PCLZIP_TEMPORARY_DIR' => Filters::FILTER_STRING,
        'PCLZIP_TEMPORARY_FILE_RATIO' => Filters::FILTER_FLOAT,
        'PLUGINS_COOKIE_PATH' => Filters::FILTER_STRING,
        'POST_BY_EMAIL' => Filters::FILTER_BOOL,
        'PO_MAX_LINE_LEN' => Filters::FILTER_INT,
        'PRIMARY_NETWORK_ID' => Filters::FILTER_INT,

        'RECOVERY_MODE_COOKIE' => Filters::FILTER_STRING,
        'RECOVERY_MODE_EMAIL' => Filters::FILTER_STRING,

        'SAVEQUERIES' => Filters::FILTER_BOOL,
        'SCRIPT_DEBUG' => Filters::FILTER_BOOL,
        'SECRET_KEY' => Filters::FILTER_RAW_STRING,
        'SECRET_SALT' => Filters::FILTER_RAW_STRING,
        'SECURE_AUTH_COOKIE' => Filters::FILTER_STRING,
        'SECURE_AUTH_KEY' => Filters::FILTER_RAW_STRING,
        'SECURE_AUTH_SALT' => Filters::FILTER_RAW_STRING,
        'SHORTINIT' => Filters::FILTER_BOOL,
        'SITECOOKIEPATH' => Filters::FILTER_STRING,
        'SITE_ID_CURRENT_SITE' => Filters::FILTER_INT,
        'SUBDOMAIN_INSTALL' => Filters::FILTER_BOOL,
        'SUNRISE' => null,

        'TEST_COOKIE' => Filters::FILTER_STRING,

        'UPLOADBLOGSDIR' => Filters::FILTER_STRING,
        'UPLOADS' => Filters::FILTER_STRING,
        'USER_COOKIE' => Filters::FILTER_STRING,

        'VHOST' => Filters::FILTER_STRING,

        'WP_ACCESSIBLE_HOSTS' => Filters::FILTER_STRING,
        'WP_ALLOW_MULTISITE' => Filters::FILTER_BOOL,
        'WP_ALLOW_REPAIR' => Filters::FILTER_BOOL,
        'WP_AUTO_UPDATE_CORE' => Filters::FILTER_STRING_OR_BOOL,
        'WP_CACHE' => Filters::FILTER_BOOL,
        'WP_CONTENT_DIR' => Filters::FILTER_STRING,
        'WP_CONTENT_URL' => Filters::FILTER_STRING,
        'WP_CRON_LOCK_TIMEOUT' => Filters::FILTER_INT,
        'WP_DEBUG' => Filters::FILTER_BOOL,
        'WP_DEBUG_DISPLAY' => Filters::FILTER_BOOL,
        'WP_DEBUG_LOG' => Filters::FILTER_STRING_OR_BOOL,
        'WP_DEFAULT_THEME' => Filters::FILTER_STRING,
        'WP_DEVELOPMENT_MODE' => Filters::FILTER_STRING,
        'WP_DISABLE_FATAL_ERROR_HANDLER' => Filters::FILTER_BOOL,
        'WP_FEATURE_BETTER_PASSWORDS' => Filters::FILTER_BOOL,
        'WP_HOME' => Filters::FILTER_STRING,
        'WP_HTTP_BLOCK_EXTERNAL' => Filters::FILTER_BOOL,
        'WP_JSON_SERIALIZE_COMPATIBLE' => Filters::FILTER_BOOL,
        'WP_LANG_DIR' => Filters::FILTER_STRING,
        'WP_LOAD_IMPORTERS' => Filters::FILTER_BOOL,
        'WP_LOCAL_DEV' => Filters::FILTER_BOOL,
        'WP_MAIL_INTERVAL' => Filters::FILTER_INT,
        'WP_MAX_MEMORY_LIMIT' => Filters::FILTER_STRING,
        'WP_MEMORY_LIMIT' => Filters::FILTER_STRING,
        'WP_PLUGIN_DIR' => Filters::FILTER_STRING,
        'WP_PLUGIN_URL' => Filters::FILTER_STRING,
        'WP_POST_REVISIONS' => Filters::FILTER_INT_OR_BOOL,
        'WP_PROXY_BYPASS_HOSTS' => Filters::FILTER_STRING,
        'WP_PROXY_HOST' => Filters::FILTER_STRING,
        'WP_PROXY_PASSWORD' => Filters::FILTER_RAW_STRING,
        'WP_PROXY_PORT' => Filters::FILTER_INT,
        'WP_PROXY_USERNAME' => Filters::FILTER_STRING,
        'WP_SITEURL' => Filters::FILTER_STRING,
        'WP_TEMP_DIR' => Filters::FILTER_STRING,
        'WP_TEMPLATE_PART_AREA_FOOTER' => Filters::FILTER_STRING,
        'WP_TEMPLATE_PART_AREA_HEADER' => Filters::FILTER_STRING,
        'WP_TEMPLATE_PART_AREA_SIDEBAR' => Filters::FILTER_STRING,
        'WP_TEMPLATE_PART_AREA_UNCATEGORIZED' => Filters::FILTER_STRING,
        'WP_USE_EXT_MYSQL' => Filters::FILTER_BOOL,
        'WP_USE_THEMES' => Filters::FILTER_BOOL,

        'WPLANG' => Filters::FILTER_STRING,
        'WPMU_ACCEL_REDIRECT' => Filters::FILTER_BOOL,
        'WPMU_PLUGIN_DIR' => Filters::FILTER_STRING,
        'WPMU_PLUGIN_URL' => Filters::FILTER_STRING,
        'WPMU_SENDFILE' => Filters::FILTER_BOOL,
    ];

    public const CACHE_DUMP_FILE = '/.env.cached.php';
    public const CUSTOM_ENV_TO_CONST_VAR_NAME = 'WP_STARTER_ENV_TO_CONST';
    public const DB_TABLE_PREFIX_VAR_NAME = 'DB_TABLE_PREFIX';
    public const WP_ADMIN_COLOR_VAR_NAME = 'WP_ADMIN_COLOR';
    public const WP_FORCE_SSL_FORWARDED_PROTO_VAR_NAME = 'WP_FORCE_SSL_FORWARDED_PROTO';
    public const WP_INSTALLED_VAR_NAME = 'WP_INSTALLED';
    public const WPDB_ENV_VALID_VAR_NAME = 'WPDB_ENV_VALID';
    public const WPDB_EXISTS_VAR_NAME = 'WPDB_EXISTS';

    public const WP_STARTER_VARS = [
        self::CUSTOM_ENV_TO_CONST_VAR_NAME => Filters::FILTER_STRING,
        self::DB_TABLE_PREFIX_VAR_NAME => Filters::FILTER_TABLE_PREFIX,
        self::WP_ADMIN_COLOR_VAR_NAME => Filters::FILTER_STRING,
        self::WP_FORCE_SSL_FORWARDED_PROTO_VAR_NAME => Filters::FILTER_BOOL,
        self::WP_INSTALLED_VAR_NAME => Filters::FILTER_BOOL,
        self::WPDB_ENV_VALID_VAR_NAME => Filters::FILTER_BOOL,
        self::WPDB_EXISTS_VAR_NAME => Filters::FILTER_BOOL,
    ];

    public const WP_STARTER_ENV_VARS = [
        'WP_ENV',
        'WORDPRESS_ENV',
        'WP_ENVIRONMENT_TYPE',
    ];

    public const ENV_TYPES = [
        'local' => 'local',
        'development' => 'development',
        'dev' => 'development',
        'develop' => 'development',
        'staging' => 'staging',
        'stage' => 'staging',
        'pre' => 'staging',
        'preprod' => 'staging',
        'pre-prod' => 'staging',
        'pre-production' => 'staging',
        'preproduction' => 'staging',
        'test' => 'staging',
        'tests' => 'staging',
        'testing' => 'staging',
        'uat' => 'staging',
        'qa' => 'staging',
        'acceptance' => 'staging',
        'accept' => 'staging',
        'production' => 'production',
        'prod' => 'production',
        'live' => 'production',
        'public' => 'production',
    ];

    private static ?Dotenv $defaultDotEnv = null;
    /** @var list<non-empty-string>|null */
    private static ?array $loadedVars = null;
    /** @var array<string, array{string, bool|int|float|string|null}> */
    private static array $cache = [];
    private ?Dotenv $dotenv;
    private ?Filters $filters = null;
    private bool $fromCache = false;
    /** @var array<string, string> */
    private array $customFiltersConfig = [];
    /** @var list<string> */
    private array $definedConstants = [];
    private ?string $envType = null;
    private bool $wordPressSetup = false;

    /**
     * @param string $file
     * @return WordPressEnvBridge
     */
    public static function buildFromCacheDump(string $file): WordPressEnvBridge
    {
        if (file_exists($file)) {
            /** @var array<string, array{string, bool|int|float|string|null}>|null $cached */
            $cached = @include $file;
            is_array($cached) and self::$cache = $cached;
        }

        $instance = new self();
        $instance->fromCache = isset($cached) && ($cached !== []);

        return $instance;
    }

    /**
     * Symfony stores a variable with the keys of variables it loads.
     *
     * @return list<non-empty-string>
     */
    public static function loadedVars(): array
    {
        if (self::$loadedVars !== null) {
            return self::$loadedVars;
        }

        self::$loadedVars = [];
        $loadedVarNames = explode(',', (getenv('SYMFONY_DOTENV_VARS') ?: ''));
        foreach ($loadedVarNames as $loadedVarName) {
            $loadedVarSafeName = trim($loadedVarName);
            if (
                ($loadedVarSafeName !== '')
                && !in_array($loadedVarSafeName, self::$loadedVars, true)
            ) {
                self::$loadedVars[] = $loadedVarSafeName;
            }
        }

        return self::$loadedVars;
    }

    /**
     * @param Dotenv|null $dotenv
     */
    public function __construct(?Dotenv $dotenv = null)
    {
        $this->dotenv = $dotenv;
    }

    /**
     * @param string $file Environment file path relative to `$path`
     * @param string|null $path Environment file path
     * @return void
     */
    public function load(string $file = '.env', ?string $path = null): void
    {
        $this->loadFile($this->fullpathFor($file, $path));
    }

    /**
     * @return string
     */
    public function determineEnvType(): string
    {
        if ($this->envType !== null) {
            return $this->envType;
        }

        $envType = 'production';

        foreach (self::WP_STARTER_ENV_VARS as $var) {
            $envByVar = $this->read($var);
            if (is_string($envByVar) && ($envByVar !== '')) {
                $envType = strtolower($envByVar);
                break;
            }
        }

        $this->envType = $envType;

        return $envType;
    }

    /**
     * @param string $path
     * @return void
     */
    public function loadFile(string $path): void
    {
        $loaded = $_ENV['WPSTARTER_ENV_LOADED'] ?? $_SERVER['WPSTARTER_ENV_LOADED'] ?? null;
        if ($loaded !== null) {
            self::$loadedVars = [];

            return;
        }

        if (self::$loadedVars !== null) {
            return;
        }

        $path = $this->fullpathFor('', $path);
        if ($path !== '') {
            $this->dotEnv()->load($path);
            self::loadedVars();
        }
    }

    /**
     * @param string $file
     * @param string|null $path
     * @return void
     */
    public function loadAppended(string $file, ?string $path = null): void
    {
        if (self::$loadedVars === null) {
            $this->load($file, $path);

            return;
        }

        $fullpath = $this->fullpathFor($file, $path);
        if ($fullpath === '') {
            return;
        }

        $contents = @file_get_contents($fullpath);
        /** @var array<string, string> $values */
        $values = (is_string($contents) && ($contents !== ''))
            ? $this->dotEnv()->parse($contents, $fullpath)
            : [];
        foreach ($values as $name => $value) {
            if ($name === '') {
                continue;
            }
            if ($this->isWritable($name)) {
                $this->write($name, $value);
                $this->isLoadedVar($name) or self::$loadedVars[] = $name;
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->read($name) !== null;
    }

    /**
     * @return bool
     */
    public function hasCachedValues(): bool
    {
        return $this->fromCache && (self::$cache !== []);
    }

    /**
     * @param string $name
     * @return mixed
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    public function read(string $name)
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity
        // phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration

        $cached = self::$cache[$name] ?? null;
        if ($cached !== null) {
            return $cached[1];
        }

        if ($this->fromCache && (self::$loadedVars === null)) {
            self::loadedVars();
        }

        // We don't check $_SERVER for keys starting with 'HTTP_' because clients can write there.
        $serverSafe = strpos($name, 'HTTP_') !== 0;

        // We consider anything not loaded by Symfony Dot Env as "actual" environment, and because
        // of thread safety issues, we don't use getenv() for those "actual" environment variables.
        $hasLoadedVars = (self::$loadedVars !== null) && (self::$loadedVars !== []);
        $loadedVar = $hasLoadedVars && $this->isLoadedVar($name);

        $readGetEnv = false;
        switch (true) {
            case ($loadedVar && $serverSafe):
                // Both $_SERVER and getenv() are ok.
                $value = $_ENV[$name] ?? $_SERVER[$name] ?? null;
                $readGetEnv = true;
                break;
            case ($loadedVar):
                // $_SERVER is not ok, getenv() is.
                $value = $_ENV[$name] ?? null;
                $readGetEnv = true;
                break;
            case ($serverSafe):
                // $_SERVER is ok, getenv() is not.
                $value = $_ENV[$name] ?? $_SERVER[$name] ?? null;
                break;
            default:
                // Neither $_SERVER nor getenv() are ok.
                $value = $_ENV[$name] ?? null;
                break;
        }

        if (($value === null) && $readGetEnv) {
            $value = getenv($name);
            ($value === false) and $value = null;
        }

        // Superglobals can contain anything, but environment variables must be strings.
        // We can cast later scalar values.
        // `is_scalar()` also discards null, and that is fine because we want to return null if
        // that's the value we got here.

        return is_scalar($value) ? $this->maybeFilterThenCache($name, (string) $value) : null;
    }

    /**
     * @param string ...$names
     * @return array<string, mixed>
     */
    public function readMany(string ...$names): array
    {
        $values = [];
        foreach ($names as $name) {
            $values[$name] = $this->read($name);
        }

        return $values;
    }

    /**
     * @param string $name
     * @param string $value
     * @return void
     */
    public function write(string $name, string $value): void
    {
        if (!$this->isWritable($name)) {
            throw new \BadMethodCallException("{$name} is not a writable ENV var.");
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        (strpos($name, 'HTTP_') !== 0) and $_SERVER[$name] = $value;

        $this->maybeFilterThenCache($name, $value);
    }

    /**
     * @param string $file
     * @return bool
     */
    public function dumpCached(string $file): bool
    {
        $symfonyLoaded = $this->loadSymfonyVarsForCache();
        if ($symfonyLoaded === null) {
            return false;
        }

        if (self::$cache === []) {
            return false;
        }

        $content = "<?php\n";

        // Store the loaded vars keys in SYMFONY_DOTENV_VARS var so that self::loadedVars() on
        // the cached instance will work.
        if ($symfonyLoaded !== '') {
            $content .= "putenv('SYMFONY_DOTENV_VARS={$symfonyLoaded}');\n\n";
        }

        foreach (self::$cache as $key => list($value, $filtered)) {
            $slashed = str_replace("'", "\'", $value);
            // For defined constants, dump the `define` with filtered value, if any.
            if (
                in_array($key, $this->definedConstants, true)
                || array_key_exists($key, self::WP_CONSTANTS)
            ) {
                $define = ($value !== $filtered)
                    ? var_export($filtered, true) // phpcs:ignore
                    : "'{$slashed}'";
                $content .= "define('{$key}', {$define});\n";
            }

            // For actual environment values, do noting.
            if (((self::$loadedVars ?? []) === []) || !$this->isLoadedVar($key)) {
                $content .= "\n";
                continue;
            }

            // For env loaded from file, dump the variable definition.
            $content .= "putenv('{$key}={$slashed}');\n";
            $content .= "\$_ENV['{$key}'] = '{$slashed}';\n";
            (strpos($key, 'HTTP_') !== 0) and $content .= "\$_SERVER['{$key}'] = '{$slashed}';\n\n";
        }

        $content .= sprintf("return %s;\n", var_export(self::$cache, true)); // phpcs:ignore

        $success = @file_put_contents($file, $content);

        return (bool) $success;
    }

    /**
     * @return string|null
     */
    private function loadSymfonyVarsForCache(): ?string
    {
        if ($this->fromCache) {
            return null;
        }

        // Make sure cached env contains all loaded vars.
        $symfonyLoaded = '';
        $toLoad = self::$loadedVars ?? [];
        foreach ($toLoad as $key) {
            $symfonyLoaded .= ($symfonyLoaded === '') ? $key : ",{$key}";
            $this->read($key);
        }

        return $symfonyLoaded;
    }

    /**
     * @return void
     */
    public function setupConstants(): void
    {
        /** @var bool $done */
        static $done;
        if ($done) {
            return;
        }

        $done = true;
        $names = $this->setupEnvConstants();
        foreach (array_keys(self::WP_CONSTANTS) as $key) {
            $this->defineConstantFromVar($key) and $names[] = $key;
        }

        $customVarsToSetRaw = $this->read(self::CUSTOM_ENV_TO_CONST_VAR_NAME);
        $customVarsToSetStr = is_string($customVarsToSetRaw) ? $customVarsToSetRaw : '';
        $customVarsToSet = explode(',', $customVarsToSetStr);
        foreach ($customVarsToSet as $customVarToSetStr) {
            $varData = explode(':', trim($customVarToSetStr), 2);
            $varName = $varData[0];
            if ($varName === '') {
                continue;
            }

            $varFilter = Filters::resolveFilterName($varData[1] ?? '');
            ($varFilter !== '') and $this->customFiltersConfig[$varName] = $varFilter;
            $this->defineConstantFromVar($varName) and $names[] = $varName;
        }

        $this->definedConstants = $names;
        $this->customFiltersConfig = [];
        $this->wordPressSetup = count(array_intersect($names, ['DB_NAME', 'DB_USER'])) === 2;
    }

    /**
     * @return list<string>
     */
    public function setupEnvConstants(): array
    {
        $names = [];
        $envType = $this->determineEnvType();
        if (!defined('WP_ENV')) {
            define('WP_ENV', $envType);
            $names[] = 'WP_ENV';
        }
        if (!defined('WP_ENVIRONMENT_TYPE')) {
            define('WP_ENVIRONMENT_TYPE', $this->determineWpEnvType($envType));
            $names[] = 'WP_ENVIRONMENT_TYPE';
        }

        return $names;
    }

    /**
     * @return bool
     */
    public function isWpSetup(): bool
    {
        return $this->wordPressSetup;
    }

    /**
     * @param string $name
     * @return bool
     */
    private function isLoadedVar(string $name): bool
    {
        return in_array($name, self::loadedVars(), true);
    }

    /**
     * @param string $name
     * @param string $value
     * @return mixed
     *
     * @phpstan-assert Filters $this->filters
     */
    private function maybeFilterThenCache(string $name, string $value)
    {
        // phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration

        $filter = in_array($name, self::WP_STARTER_ENV_VARS, true) ? Filters::FILTER_STRING : null;

        $filter = $filter
            ?? self::WP_CONSTANTS[$name]
            ?? self::WP_STARTER_VARS[$name]
            ?? $this->customFiltersConfig[$name]
            ?? null;

        if (($filter === null) || ($filter === '')) {
            self::$cache[$name] = [$value, $value];

            return $value;
        }

        $this->filters ??= new Filters();
        $filtered = $this->filters->filter($filter, $value);
        self::$cache[$name] = [$value, $filtered];

        return $filtered;
    }

    /**
     * @param string $name
     * @return bool True if a constant has been defined.
     */
    private function defineConstantFromVar(string $name): bool
    {
        $value = $this->read($name);

        if ($value === null) {
            return false;
        }

        defined($name) or define($name, $value);

        return true;
    }

    /**
     * @param string $envType
     * @return string
     */
    private function determineWpEnvType(string $envType): string
    {
        $rawWpEnv = $this->read('WP_ENVIRONMENT_TYPE');
        if (is_string($rawWpEnv) && ($rawWpEnv !== '')) {
            $envType = strtolower($rawWpEnv);
        }

        $envTypeWp = self::ENV_TYPES[$envType] ?? null;
        if ($envTypeWp !== null) {
            return $envTypeWp;
        }

        foreach (self::ENV_TYPES as $envTypeName => $envTypeMapped) {
            if (preg_match("~(?:^|[^a-z]+){$envTypeName}(?:[^a-z]+|$)~", $envType) === 1) {
                return $envTypeMapped;
            }
        }

        return 'production';
    }

    /**
     * @param string $name
     * @return bool
     */
    private function isWritable(string $name): bool
    {
        return !$this->has($name) || $this->isLoadedVar($name);
    }

    /**
     * @param string $filename
     * @param string|null $basePath
     * @return string
     */
    private function fullpathFor(string $filename, ?string $basePath = null): string
    {
        $basePath === null and $basePath = getcwd();

        $fullpath = realpath(rtrim(rtrim((string) $basePath, '\\/') . "/{$filename}", '\\/'));
        if (($fullpath === false) || !is_file($fullpath) || !is_readable($fullpath)) {
            return '';
        }

        return $fullpath;
    }

    /**
     * @return Dotenv
     */
    private function dotEnv(): Dotenv
    {
        $dotEnv = $this->dotenv ?? self::$defaultDotEnv;
        if ($dotEnv === null) {
            self::$defaultDotEnv = new Dotenv();
            self::$defaultDotEnv->usePutenv(true);
            $dotEnv = self::$defaultDotEnv;
        }

        return $dotEnv;
    }
}
