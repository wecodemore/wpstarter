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
     * @param array $command
     * @param string|null $cwd
     * @param string[] $args
     * @return bool
     */
    public function execute(array $command, string $cwd = null, string ...$args): bool
    {
        array_unshift($command, $this->phpPath);

        return $this->process->execute($command, $cwd, ...$args);
    }

    /**
     * @return string
     */
    public function phpPath(): string
    {
        return $this->phpPath;
    }
}
