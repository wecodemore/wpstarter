<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\WpCli;

use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\Paths;

class Executor
{
    /**
     * @var string
     */
    private $phpPath;

    /**
     * @var string
     */
    private $cliPath;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var Command
     */
    private $command;

    /**
     * @param string $phpPath
     * @param string $cliPath
     * @param Paths $paths
     * @param Io $io
     * @param Command $command
     */
    public function __construct(
        string $phpPath,
        string $cliPath,
        Paths $paths,
        Io $io,
        Command $command
    ) {

        $this->phpPath = $phpPath;
        $this->cliPath = $cliPath;
        $this->paths = $paths;
        $this->io = $io;
        $this->command = $command;
    }

    /**
     * @param string $command
     */
    public function execute(string $command)
    {
        $preparedCommand = $this->command->prepareCommand($command, $this->paths);

        passthru("{$this->phpPath} {$this->cliPath} {$preparedCommand}");
    }
}
