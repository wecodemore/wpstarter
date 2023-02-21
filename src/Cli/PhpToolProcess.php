<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Cli;

use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\Paths;

class PhpToolProcess
{
    /**
     * @var PhpTool
     */
    private $tool;

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
     * @var Io
     */
    private $io;

    /**
     * @param string $phpPath
     * @param PhpTool $tool
     * @param string $toolPath
     * @param Paths $paths
     * @param Io $io
     */
    public function __construct(
        string $phpPath,
        PhpTool $tool,
        string $toolPath,
        Paths $paths,
        Io $io
    ) {

        $this->tool = $tool;
        $this->phpProcess = new PhpProcess($phpPath, $paths, $io);
        $this->paths = $paths;
        $this->io = $io;
        $this->toolPath = $toolPath;
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
        return $this->phpProcess->execute(
            $this->tool->prepareCommand(
                $this->toolPath ? "{$this->toolPath} {$command}" : $command,
                $this->paths,
                $this->io
            )
        );
    }

    /**
     * @param string $command
     * @param string|null $cwd
     * @return bool
     */
    public function executeSilently(string $command, ?string $cwd = null): bool
    {
        return $this->phpProcess->executeSilently(
            $this->tool->prepareCommand(
                "{$this->toolPath} {$command}",
                $this->paths,
                $this->io
            ),
            $cwd
        );
    }
}
