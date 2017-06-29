<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\PhpCliTool;

use Symfony\Component\Process\PhpExecutableFinder;
use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\Paths;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WeCodeMore\WpStarter
 * @license http://opensource.org/licenses/MIT MIT
 */
class CommandExecutor
{

    /**
     * @var bool
     */
    private static $executing = false;

    /**
     * @var false|string
     */
    private static $php;

    /**
     * @var string
     */
    private $cliPath;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var IO
     */
    private $io;

    /**
     * @var ToolInterface
     */
    private $tool;

    /**
     * @return bool
     */
    public static function executing()
    {
        return self::$executing;
    }

    /**
     * @param string $cliPath
     * @param Paths $paths
     * @param IO $io
     * @param ToolInterface $tool
     */
    public function __construct($cliPath, Paths $paths, IO $io, ToolInterface $tool)
    {
        if (!isset(self::$php)) {
            $executorFinder = new PhpExecutableFinder();
            self::$php = $executorFinder->find();
        }

        $this->cliPath = $cliPath;
        $this->paths = $paths;
        $this->io = $io;
        $this->tool = $tool;
    }

    /**
     * @param string $command
     */
    public function execute($command)
    {
        self::$executing = $this->tool->packageName();
        
        $command = $this->tool->prepareCommand($command, $this->paths, $this->io);

        if ($command && is_string($command) && $this->checkPhpExecutor($command)) {
            passthru(self::$php . "{$this->cliPath} {$command}");
        }

        self::$executing = false;
    }

    /**
     * @param string $command
     * @return bool
     */
    private function checkPhpExecutor($command)
    {
        if (!self::$php) {
            $this->io->error(
                sprintf(
                    'Can\'t execute %s `%s`: unable to locate PHP executable.',
                    $this->tool->niceName(),
                    $command
                )
            );

            return false;
        }

        return true;
    }
}