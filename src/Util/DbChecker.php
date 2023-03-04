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

    /**
     * @var WordPressEnvBridge
     */
    private $env;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var SystemProcess
     */
    private $process;

    /**
     * @var ExecutableFinder
     */
    private $finder;

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
        $this->check();

        return (bool)$this->env->read(self::WPDB_EXISTS);
    }

    /**
     * @return bool
     */
    public function isInstalled(): bool
    {
        $this->check();

        return (bool)$this->env->read(self::WP_INSTALLED);
    }

    /**
     * @return bool
     */
    public function isEnvValid(): bool
    {
        $this->check();

        return (bool)$this->env->read(self::WPDB_ENV_VALID);
    }

    /**
     * @return void
     */
    public function check()
    {
        if (
            $this->env->has(self::WPDB_ENV_VALID)
            || $this->env->has(self::WPDB_EXISTS)
            || $this->env->has(self::WP_INSTALLED)
        ) {
            return;
        }

        /** @var array<string, string> $env */
        $env = $this->env->readMany(
            'DB_HOST',
            'DB_USER',
            'DB_NAME',
            'DB_PASSWORD',
            'DB_TABLE_PREFIX'
        );

        if (!$env['DB_USER'] || !$env['DB_NAME']) {
            $this->write('Environment not ready, DB status can\'t be checked.');
            $this->setupEnv(false, false, false);

            return;
        }

        empty($env['DB_HOST']) and $env['DB_HOST'] = 'localhost';
        empty($env['DB_TABLE_PREFIX']) and $env['DB_TABLE_PREFIX'] = 'wp_';

        $dbExists = false;
        $wpInstalled = false;

        try {
            $db = @\mysqli_connect($env['DB_HOST'], $env['DB_USER'], $env['DB_PASSWORD'] ?: '');

            if (!$db || $db->connect_errno) {
                $this->setupEnv(false, false, false);
                $db and \mysqli_close($db);

                return;
            }

            $dbExists = @\mysqli_select_db($db, $env['DB_NAME']);


            if ($dbExists) {
                $result = @mysqli_query($db, "SELECT 1 FROM {$env['DB_TABLE_PREFIX']}users");
                $wpInstalled = ($result instanceof \mysqli_result) && $result->field_count;
            }
            @\mysqli_close($db);
        } catch (\Throwable $exception) {
            $this->write($exception->getMessage());
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
     * @return bool
     */
    public function mysqlcheck(): bool
    {
        $checker = $this->finder->find('mysqlcheck');
        if (!$checker) {
            $this->io->writeError('Sorry, mysqlcheck not found, can not check DB.');

            return false;
        }
        $user = $this->env->read('DB_USER');
        $password = $this->env->read('DB_PASSWORD');
        $command = sprintf(
            'mysqlcheck --no-defaults "%s" --check --default-character-set="utf8" --host="%s"',
            (string)$this->env->read('DB_NAME'),
            (string)$this->env->read('DB_HOST')
        );
        $user and $command .= " --user=\"{$user}\"";
        $password and $command .= " --password=\"{$password}\"";
        $command .= ' --default-character-set="utf8"';
        $this->io->writeCommentIfVerbose('- Checking database via mysqlcheck...');
        $ok = $this->process->executeSilently($command);

        $ok
            ? $this->io->write('- <info>[WPDB Check]</info> Database tables are OK')
            : $this->io->writeError('Database check failed');

        return $ok;
    }

    /**
     * @param bool $valid
     * @param bool $exists
     * @param bool $installed
     * @return void
     */
    private function setupEnv(bool $valid, bool $exists, bool $installed)
    {
        $this->env->write(self::WPDB_ENV_VALID, $valid ? '1' : '');
        $this->env->write(self::WPDB_EXISTS, $exists ? '1' : '');
        $this->env->write(self::WP_INSTALLED, $installed ? '1' : '');
    }

    /**
     * @param string $line
     * @return void
     */
    private function write(string $line)
    {
        $this->io->writeIfVerbose("- <info>[WPDB Check]</info> <comment>{$line}</comment>");
    }
}
