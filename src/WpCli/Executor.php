<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\WpCli;

use Symfony\Component\Process\PhpExecutableFinder;
use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\Paths;

class Executor
{
    /**
     * @var bool
     */
    private static $executing = false;

    /**
     * @var string
     */
    private static $php;

    /**
     * @var string
     */
    private $cliPath;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var Command
     */
    private $command;

    /**
     * @return bool
     */
    public static function executing(): bool
    {
        return self::$executing;
    }

    /**
     * @param string $cliPath
     * @param Paths $paths
     * @param Io $io
     * @param Command $command
     */
    public function __construct(string $cliPath, Paths $paths, Io $io, Command $command)
    {
        if (self::$php === null) {
            self::$php = (new PhpExecutableFinder())->find() ?: '';
        }

        $this->cliPath = $cliPath;
        $this->paths = $paths;
        $this->io = $io;
        $this->command = $command;
    }

    /**
     * @param string $command
     */
    public function execute(string $command)
    {
        self::$executing = $this->command->packageName();

        $command = $this->command->prepareCommand($command, $this->paths);

        if ($this->checkPhpExecutor($command)) {
            passthru(self::$php . " {$this->cliPath} {$command}");
        }

        self::$executing = false;
    }

    /**
     * @param string $command
     * @return bool
     */
    private function checkPhpExecutor(string $command): bool
    {
        if (!self::$php) {
            $this->io->error(
                sprintf(
                    'Can\'t execute %s `%s`: unable to locate PHP executable.',
                    $this->command->niceName(),
                    $command
                )
            );

            return false;
        }

        return true;
    }
}
