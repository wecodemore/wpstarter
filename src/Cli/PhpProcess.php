<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Cli;

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
     * @param SystemProcess $process
     */
    public function __construct(string $phpPath, SystemProcess $process)
    {
        $this->phpPath = $phpPath;
        $this->process = $process;
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
