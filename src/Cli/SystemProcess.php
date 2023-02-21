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
    /**
     * @var callable|null
     */
    private $printer;

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
     * @return bool
     */
    public function execute(string $command, ?string $cwd = null): bool
    {
        try {
            is_string($cwd) or $cwd = $this->paths->root();

            $process = $this->factoryProcess($command, $cwd);

            $this->printer or $this->printer = function (string $type, string $buffer) {
                $lines = array_filter(array_map('rtrim', explode("\n", $buffer)));
                Process::ERR === $type
                    ? array_walk($lines, [$this->io, 'writeError'])
                    : array_walk($lines, [$this->io, 'write']);
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
            is_string($cwd) or $cwd = $this->paths->root();
            $process = $this->factoryProcess($command, $cwd);
            $process->disableOutput()->mustRun();

            return $process->isSuccessful();
        } catch (\Throwable $exception) {
            $this->io->writeErrorIfVerbose($exception->getMessage());

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
}
