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

class PhpProcess
{
    /**
     * @var string
     */
    private $phpPath;

    /**
     * @var SystemProcess
     */
    private $process;

    /**
     * @param string $phpPath
     * @param Paths $paths
     * @param Io $io
     */
    public function __construct(string $phpPath, Paths $paths, Io $io)
    {
        $this->phpPath = $phpPath;
        $this->process = new SystemProcess($paths, $io);
    }

    /**
     * @param array $environment
     * @return PhpProcess
     */
    public function withEnvironment(array $environment): PhpProcess
    {
        $this->process = $this->process->withEnvironment($environment);

        return $this;
    }

    /**
     * @param string $command
     * @param string|null $cwd
     * @return bool
     */
    public function execute(string $command, ?string $cwd = null): bool
    {
        return $this->process->execute("{$this->phpPath} {$command}", $cwd);
    }

    /**
     * @param string $command
     * @param string|null $cwd
     * @return bool
     */
    public function executeSilently(string $command, ?string $cwd = null): bool
    {
        return $this->process->executeSilently("{$this->phpPath} {$command}", $cwd);
    }
}
