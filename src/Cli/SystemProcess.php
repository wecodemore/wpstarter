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
     * @param array $command
     * @param string|null $cwd
     * @param string[] $args
     * @return bool
     */
    public function execute(array $command, string $cwd = null, string ...$args): bool
    {
        try {
            is_string($cwd) or $cwd = $this->paths->root();
            $process = new Process($command, $cwd, $this->environment);

            $this->printer or $this->printer = function (string $type, string $buffer) {
                Process::ERR === $type
                    ? $this->io->writeErrorLine($buffer)
                    : $this->io->write($buffer);
            };

            array_unshift($args, $this->printer);
            $process->mustRun(...$args);

            return $process->isSuccessful();
        } catch (\Throwable $exception) {
            $this->io->write($exception->getMessage());

            return false;
        }
    }
}
