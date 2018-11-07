<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use WeCodeMore\WpStarter\Env\WordPressEnvBridge;

/**
 * Check status of WP DB and set two constants holding status for DB found and WP installed.
 */
class DbChecker
{
    const WP_INSTALLED = 'WP_INSTALLED';
    const WPDB_EXISTS = 'WPDB_EXISTS';
    const WPDB_ENV_VALID = 'WPDB_ENV_VALID';

    /**
     * @var WordPressEnvBridge
     */
    private $env;

    /**
     * @var Io
     */
    private $io;

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

        return (bool)$this->env[self::WPDB_EXISTS];
    }

    /**
     * @return bool
     */
    public function isInstalled(): bool
    {
        $this->check();

        return (bool)$this->env[self::WP_INSTALLED];
    }

    /**
     * @return bool
     */
    public function isEnvValid(): bool
    {
        $this->check();

        return (bool)$this->env[self::WPDB_ENV_VALID];
    }

    /**
     * @return void
     */
    public function check()
    {
        if ($this->env->has(self::WPDB_ENV_VALID)
            || $this->env->has(self::WPDB_EXISTS)
            || $this->env->has(self::WP_INSTALLED)
        ) {
            return;
        }

        if (!$this->env['DB_HOST'] || !$this->env['DB_USER'] || !$this->env['DB_NAME']) {
            $this->write('Environment not ready, DB status can\'t be checked.');
            $this->setupEnv(false, false, false);

            return;
        }

        $db = @\mysqli_connect(
            $this->env['DB_HOST'],
            $this->env['DB_USER'],
            $this->env['DB_PASSWORD'] ?: ''
        );

        if (!$db || $db->connect_errno) {
            $this->setupEnv(false, false, false);
            $db and \mysqli_close($db);

            return;
        }

        $dbExists = @\mysqli_select_db($db, $this->env['DB_NAME']);

        $wpInstalled = false;
        if ($dbExists) {
            $prefix = $this->env['DB_TABLE_PREFIX'] ?: 'wp_';
            $result = @mysqli_query($db, "SELECT 1 FROM {$prefix}users");
            $wpInstalled = $result && $result->field_count;
        }
        \mysqli_close($db);
        $this->setupEnv(true, $dbExists, $wpInstalled);

        switch (true) {
            case $wpInstalled:
                $this->write('DB found and WP looks installed.');
                break;
            case $dbExists:
                $this->write('DB found, but WP looks not installed.');
                break;
            default:
                $this->write('DB not found.');
                break;
        }
    }

    /**
     * @param bool $valid
     * @param bool $exists
     * @param bool $installed
     * @return void
     */
    private function setupEnv(bool $valid, bool $exists, bool $installed)
    {
        $this->env->write(self::WPDB_ENV_VALID, ($valid ? '1' : ''));
        $this->env->write(self::WPDB_EXISTS, ($exists ? '1' : ''));
        $this->env->write(self::WP_INSTALLED, ($installed ? '1' : ''));
    }

    /**
     * @param string $line
     * @return void
     */
    private function write(string $line)
    {
        $this->io->writeIfVerbose("- <comment>{$line}</comment>");
    }
}
