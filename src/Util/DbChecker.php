<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use WeCodeMore\WpStarter\Env\WordPressEnvBridge;
use WeCodeMore\WpStarter\Io\Io;

/**
 * Check status of WP DB and set two constants holding status for DB found and WP installed.
 */
class DbChecker
{
    public const WP_INSTALLED = 'WP_INSTALLED';
    public const WPDB_EXISTS = 'WPDB_EXISTS';
    public const WPDB_ENV_VALID = 'WPDB_ENV_VALID';

    private WordPressEnvBridge $env;
    private Io $io;

    /**
     * @param WordPressEnvBridge $env
     * @param Io $io
     */
    public function __construct(WordPressEnvBridge $env, Io $io)
    {
        $this->env = $env;
        $this->io = $io;
    }

    /**
     * @return bool
     */
    public function dbExists(): bool
    {
        $this->check();

        return (bool) $this->env->read(self::WPDB_EXISTS);
    }

    /**
     * @return bool
     */
    public function isInstalled(): bool
    {
        $this->check();

        return (bool) $this->env->read(self::WP_INSTALLED);
    }

    /**
     * @return bool
     */
    public function isEnvValid(): bool
    {
        $this->check();

        return (bool) $this->env->read(self::WPDB_ENV_VALID);
    }

    /**
     * @return void
     */
    public function check(): void
    {
        [$user, $dbname, $host, $password, $prefix] = $this->readEnv();
        if (($user === null) || ($dbname === null)) {
            $this->setupEnv(false, false, false);

            return;
        }

        $dbExists = false;
        $wpInstalled = false;
        $db = null;

        try {
            $db = @\mysqli_connect($host ?? 'localhost', $user, $password ?? '');
            $success = (bool) $db;

            if (!$success || ($db->connect_errno > 0)) {
                $this->setupEnv(false, false, false);
                ($db instanceof \mysqli) and \mysqli_close($db);

                return;
            }

            $dbExists = @\mysqli_select_db($db, $dbname);
            if ($dbExists) {
                $result = @mysqli_query($db, "SELECT 1 FROM `{$prefix}users`");
                $wpInstalled = ($result instanceof \mysqli_result) && ($result->field_count > 0);
            }
        } catch (\Throwable $exception) {
            $this->write($exception->getMessage());
        } finally {
            ($db instanceof \mysqli) and @\mysqli_close($db);
        }

        $this->setupEnv(true, $dbExists, $wpInstalled);

        switch (true) {
            case $wpInstalled:
                $this->write('DB found and WordPress looks installed.');
                break;
            case $dbExists:
                $this->write('DB found, but WordPress looks not installed.');
                break;
            default:
                $this->write('DB not found.');
                break;
        }
    }

    /**
     * @return array{
     *     null|non-empty-string,
     *     null|non-empty-string,
     *     null|non-empty-string,
     *     null|non-empty-string,
     *     non-empty-string
     * }
     */
    private function readEnv(): array
    {
        if (
            $this->env->has(self::WPDB_ENV_VALID)
            || $this->env->has(self::WPDB_EXISTS)
            || $this->env->has(self::WP_INSTALLED)
        ) {
            return [null, null, null, null, 'wp_'];
        }

        /** @var array<string, mixed> $data */
        $data = $this->env->readMany(
            'DB_USER',
            'DB_NAME',
            'DB_HOST',
            'DB_PASSWORD',
            'DB_TABLE_PREFIX'
        );

        $user = $this->readEnvVar('DB_USER', $data);
        $dbname = $this->readEnvVar('DB_NAME', $data);
        if (($user === null) || ($dbname === null)) {
            $this->write('Environment not ready, DB status can\'t be checked.');
            $this->setupEnv(false, false, false);

            return [null, null, null, null, 'wp_'];
        }

        $host = $this->readEnvVar('DB_HOST', $data) ?? 'localhost';
        $password = $this->readEnvVar('DB_PASSWORD', $data);
        $prefix = $this->readEnvVar('DB_TABLE_PREFIX', $data) ?? 'wp_';

        return [$user, $dbname, $host, $password, $prefix];
    }

    /**
     * @param "DB_HOST"|"DB_USER"|"DB_NAME"|"DB_PASSWORD"|"DB_TABLE_PREFIX" $key
     * @param array<string, mixed> $data
     * @return non-empty-string|null
     */
    private function readEnvVar(string $key, array $data): ?string
    {
        $value = $data[$key];
        if (!is_string($value) || ($value === '')) {
            $value = null;
        }

        return $value;
    }

    /**
     * @param bool $valid
     * @param bool $exists
     * @param bool $installed
     * @return void
     */
    private function setupEnv(bool $valid, bool $exists, bool $installed): void
    {
        $this->env->write(self::WPDB_ENV_VALID, $valid ? '1' : '');
        $this->env->write(self::WPDB_EXISTS, $exists ? '1' : '');
        $this->env->write(self::WP_INSTALLED, $installed ? '1' : '');
    }

    /**
     * @param string $line
     * @return void
     */
    private function write(string $line): void
    {
        $this->io->writeIfVerbose("- <info>[WPDB Check]</info> <comment>{$line}</comment>");
    }
}
