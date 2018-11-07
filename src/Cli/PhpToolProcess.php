<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Cli;

use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\Paths;

class PhpToolProcess
{
    /**
     * @var string
     */
    private $toolPath;

    /**
     * @var PhpProcess
     */
    private $phpProcess;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @param string $phpPath
     * @param string $cliPath
     * @param Paths $paths
     * @param Io $io
     */
    public function __construct(string $phpPath, string $cliPath, Paths $paths, Io $io)
    {
        $this->toolPath = realpath($cliPath);
        $this->phpProcess = new PhpProcess($phpPath, $paths, $io);
        $this->paths = $paths;
    }

    /**
     * @param array $environment
     * @return PhpToolProcess
     */
    public function withEnvironment(array $environment): PhpToolProcess
    {
        $this->phpProcess = $this->phpProcess->withEnvironment($environment);

        return $this;
    }

    /**
     * @param string $command
     * @return bool
     */
    public function execute(string $command): bool
    {
        $commandLine = sprintf('%s %s --path=%s', $this->toolPath, $command, $this->paths->wp());

        return $this->phpProcess->execute($commandLine);
    }

    /**
     * @return string
     */
    public function toolPath(): string
    {
        return $this->toolPath;
    }
}
