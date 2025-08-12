<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Cli;

use Symfony\Component\Process\Process;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\Paths;

class SystemProcess
{
    /** @var callable|null */
    private $printer = null;
    private Paths $paths;
    private Io $io;
    /** @var array<string, string> */
    private array $environment = [];

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
     * @param array<string, string> $environment
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
     * @return bool
     */
    public function execute(string $command, ?string $cwd = null): bool
    {
        try {
            $cwd ??= $this->paths->root();

            $process = $this->factoryProcess($command, $cwd);

            $this->printer ??= function (string $type, string $buffer): void {
                $this->printer($type, $buffer);
            };

            $process->mustRun($this->printer);

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
     * @return bool
     */
    public function executeSilently(string $command, ?string $cwd = null): bool
    {
        try {
            $cwd ??= $this->paths->root();
            $process = $this->factoryProcess($command, $cwd);
            $process->disableOutput()->mustRun();

            return $process->isSuccessful();
        } catch (\Throwable $exception) {
            $this->io->writeErrorIfVerbose($exception->getMessage());

            return false;
        }
    }

    /**
     * @param string $type
     * @param string $buffer
     * @return void
     */
    private function printer(string $type, string $buffer): void
    {
        foreach (explode("\n", $buffer) as $rawLine) {
            $line = rtrim($rawLine);
            if ($line === '') {
                continue;
            }
            (Process::ERR === $type)
                ? $this->io->writeError($line)
                : $this->io->write($line);
        }
    }

    /**
     * @param string $command
     * @param string|null $cwd
     * @return Process
     */
    private function factoryProcess(string $command, ?string $cwd = null): Process
    {
        return Process::fromShellCommandline($command, $cwd, $this->environment ?: null);
    }
}
