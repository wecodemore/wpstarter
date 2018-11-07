<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Cli;

use Symfony\Component\Process\Process;
use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\Paths;

class SystemProcess
{
    /**
     * @var callable
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
    public function execute(string $command, string $cwd = null): bool
    {
        try {
            is_string($cwd) or $cwd = $this->paths->root();

            $process = new Process($command, $cwd, $this->environment ?: null);

            $this->printer or $this->printer = function (string $type, string $buffer) {
                $lines = array_map('rtrim', explode("\n", $buffer));
                Process::ERR === $type
                    ? array_walk($lines, [$this->io, 'writeErrorLine'])
                    : array_walk($lines, [$this->io, 'write']);
            };

            $process->mustRun($this->printer);

            return $process->isSuccessful();
        } catch (\Throwable $exception) {
            $lines = array_map('rtrim', explode("\n", $exception->getMessage()));
            array_walk($lines, [$this->io, 'writeErrorLine']);

            return false;
        }
    }
}
