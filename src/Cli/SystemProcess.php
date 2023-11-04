<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Cli;

use Composer\IO\IOInterface;
use Symfony\Component\Process\Process;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\Paths;

class SystemProcess
{
    /**
     * @var array<int, callable>
     */
    private $printers = [];

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var array
     */
    private $environment = [];

    /**
     * @var Io
     */
    private $io;

    /**
     * @param Paths $paths
     * @param Io $io
     */
    public function __construct(Paths $paths, Io $io)
    {
        $this->paths = $paths;
        $this->io = $io;
    }

    /**
     * @param array $environment
     * @return SystemProcess
     */
    public function withEnvironment(array $environment): SystemProcess
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * @param string $command
     * @param string|null $cwd
     * @param int $verbosity
     * @return bool
     */
    public function execute(
        string $command,
        ?string $cwd = null,
        int $verbosity = IOInterface::NORMAL
    ): bool {

        if ($verbosity <= IOInterface::QUIET) {
            return $this->executeSilently($command, $cwd);
        }

        try {
            is_string($cwd) or $cwd = $this->paths->root();

            $process = $this->factoryProcess($command, $cwd);
            $process->mustRun($this->factoryPrinter($verbosity));

            return $process->isSuccessful();
        } catch (\Throwable $exception) {
            $lines = array_map('rtrim', explode("\n", $exception->getMessage()));
            array_walk($lines, [$this->io, 'writeError']);

            return false;
        }
    }

    /**
     * @param string $command
     * @param string|null $cwd
     * @return array{string, string, bool, \Throwable|null}
     */
    public function executeCapturing(string $command, ?string $cwd = null): array
    {
        $out = '';
        $err = '';
        $printer = static function (string $type, string $buffer) use (&$out, &$err): void {
            /**
             * @var string $out
             * @var string $err
             */
            (Process::ERR === $type) ? ($err .= $buffer) : ($out .= $buffer);
        };

        /**
         * @var string $out
         * @var string $err
         */
        try {
            is_string($cwd) or $cwd = $this->paths->root();

            $process = $this->factoryProcess($command, $cwd);
            $process->mustRun($printer);
            return [$out, $err, $process->isSuccessful(), null];
        } catch (\Throwable $exception) {
            return [$out, $err, false, $exception];
        }
    }

    /**
     * @param string $command
     * @param string|null $cwd
     * @return bool
     */
    public function executeSilently(string $command, ?string $cwd = null): bool
    {
        try {
            is_string($cwd) or $cwd = $this->paths->root();
            $process = $this->factoryProcess($command, $cwd);
            $process->disableOutput()->mustRun();

            return $process->isSuccessful();
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * @param string $command
     * @param string|null $cwd
     * @return Process
     */
    private function factoryProcess(string $command, ?string $cwd = null): Process
    {
        if (method_exists(Process::class, 'fromShellCommandline')) {
            return Process::fromShellCommandline($command, $cwd, $this->environment ?: null);
        }

        /** @psalm-suppress InvalidArgument */
        return new Process($command, $cwd, $this->environment ?: null);
    }

    /**
     * @param int $vvv
     * @return callable
     */
    private function factoryPrinter(int $vvv = IOInterface::NORMAL): callable
    {
        $ifVerbose = $vvv >= IOInterface::VERBOSE;
        $key = $ifVerbose ? IOInterface::VERBOSE : IOInterface::NORMAL;
        if (isset($this->printers[$key])) {
            return $this->printers[$key];
        }

        $this->printers[$key] = function (string $type, string $buffer) use ($ifVerbose): void {
            $write = $ifVerbose ? 'writeIfVerbose' : 'write';
            $writeError = $ifVerbose ? 'writeErrorIfVerbose' : 'writeError';
            $lines = array_filter(array_map('rtrim', explode("\n", $buffer)));
            Process::ERR === $type
                ? array_walk($lines, [$this->io, $writeError])
                : array_walk($lines, [$this->io, $write]);
        };

        return $this->printers[$key];
    }
}
