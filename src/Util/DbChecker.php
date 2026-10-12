<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use Symfony\Component\Process\ExecutableFinder;
use WeCodeMore\WpStarter\Cli\SystemProcess;
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
    public const HEALTH_CHECK = 'health';

    private WordPressEnvBridge $env;
    private Io $io;
    private SystemProcess $process;
    private ExecutableFinder $finder;
    private ?bool $exists = null;
    private ?bool $installed = null;

    /**
     * @param WordPressEnvBridge $env
     * @param Io $io
     * @param SystemProcess $process
     * @param ExecutableFinder $finder
     */
    public function __construct(
        WordPressEnvBridge $env,
        Io $io,
        SystemProcess $process,
        ExecutableFinder $finder
    ) {

        $this->env = $env;
        $this->io = $io;
        $this->process = $process;
        $this->finder = $finder;
    }

    /**
     * @return bool
     */
    public function dbExists(): bool
    {
        if (!is_bool($this->exists)) {
            $this->check();
        }

        return (bool) $this->exists;
    }

    /**
     * @return bool
     */
    public function isInstalled(): bool
    {
        if (!is_bool($this->installed)) {
            $this->check();
        }

        return (bool) $this->installed;
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
        if ($this->hasCheckVars()) {
            return;
        }

        /** @var array<string, mixed> $data */
        $data = $this->env->readMany(
            'DB_HOST',
            'DB_USER',
            'DB_NAME',
            'DB_PASSWORD',
            'DB_TABLE_PREFIX'
        );

        $user = $this->readEnvVar('DB_USER', $data);
        $dbname = $this->readEnvVar('DB_NAME', $data);

        if (($user === null) || ($dbname === null)) {
            $this->write('Environment not ready, DB status can\'t be checked.');
            $this->setupEnv(false, false, false);

            return;
        }

        $host = $this->readEnvVar('DB_HOST', $data) ?? 'localhost';
        $password = $this->readEnvVar('DB_PASSWORD', $data);
        $prefix = $this->readEnvVar('DB_TABLE_PREFIX', $data) ?? 'wp_';

        [$dbExists, $wpInstalled] = $this->tryConnection(
            $host,
            $dbname,
            $prefix,
            $user,
            $password ?: ''
        );

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
     * @return bool
     */
    public function mysqlcheck(): bool
    {
        if (!$this->dbExists()) {
            return false;
        }

        $checker = $this->finder->find('mysqlcheck');
        if ($checker === null) {
            $this->writeError('Sorry, mysqlcheck not found, can not check DB health.');

            return false;
        }
        $user = $this->env->read('DB_USER');
        $password = $this->env->read('DB_PASSWORD');
        /** @var string $dbName */
        $dbName = $this->env->read('DB_NAME');
        /** @var string $dbHost */
        $dbHost = $this->env->read('DB_HOST');
        $command = sprintf(
            'mysqlcheck --no-defaults "%s" --check --default-character-set="utf8" --host="%s"',
            $dbName,
            $dbHost
        );
        is_string($user) && $user !== '' and $command .= " --user=\"{$user}\"";
        is_string($password) && $password !== '' and $command .= " --password=\"{$password}\"";
        $command .= ' --default-character-set="utf8"';
        $this->io->writeCommentIfVerbose('- Checking database via mysqlcheck...');
        $ok = $this->process->executeSilently($command);

        $ok
            ? $this->write('Database tables are OK')
            : $this->writeError('Database check failed');

        return $ok;
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
        $this->exists = $exists;
        $this->installed = $installed;
    }

    /**
     * @param string $line
     * @return void
     */
    private function write(string $line): void
    {
        $this->io->writeIfVerbose("- <info>[WPDB Check]</info> <comment>{$line}</comment>");
    }

    /**
     * @param string $line
     * @return void
     */
    private function writeError(string $line): void
    {
        $this->io->writeErrorIfVerbose("- [WPDB Check] {$line}");
    }

    /**
     * @param string $host
     * @param string $dbName
     * @param string $dbPrefix
     * @param string $user
     * @param string $password
     * @return array{bool, bool}
     */
    private function tryConnection(
        string $host,
        string $dbName,
        string $dbPrefix,
        string $user,
        string $password
    ): array {

        $dbExists = false;
        $wpInstalled = false;

        try {
            $db = @\mysqli_connect($host, $user, $password ?: '');

            if (!$db instanceof \mysqli || $db->connect_errno !== 0) {
                $this->setupEnv(false, false, false);
                $db instanceof \mysqli and \mysqli_close($db);

                return [$dbExists, $wpInstalled];
            }

            $dbExists = @\mysqli_select_db($db, $dbName);
            if ($dbExists) {
                $result = @mysqli_query($db, "SELECT 1 FROM {$dbPrefix}users");
                $wpInstalled = ($result instanceof \mysqli_result) && $result->field_count > 0;
            }
            @\mysqli_close($db);

            return [$dbExists, $wpInstalled];
        } catch (\Throwable $exception) {
            $this->write($exception->getMessage());

            return [$dbExists, $wpInstalled];
        }
    }

    /**
     * @return bool
     */
    private function hasCheckVars(): bool
    {
        return $this->env->has(self::WPDB_ENV_VALID)
            || $this->env->has(self::WPDB_EXISTS)
            || $this->env->has(self::WP_INSTALLED);
    }
}
