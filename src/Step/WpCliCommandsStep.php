<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\WpCli;

final class WpCliCommandsStep implements Step
{

    const NAME = 'wp-cli-commands';

    /**
     * @var string[]
     */
    private $commands = [];

    /**
     * @var WpCli\FileData[]
     */
    private $files = [];

    /**
     * @var Io
     */
    private $io;

    /**
     * @var WpCli\Executor
     */
    private $executor;

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->io = $locator->io();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        $executor = $config[Config::WP_CLI_EXECUTOR]->unwrapOrFallback();
        if ($executor) {
            $this->executor = $executor;
            $this->commands = $config[Config::WP_CLI_COMMANDS]->unwrapOrFallback([]);
            $this->files = $config[Config::WP_CLI_FILES]->unwrapOrFallback([]);

            return true;
        }

        return false;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        if ((!$this->commands && !$this->files) || !$this->executor) {
            return self::NONE;
        }

        $this->io->writeComment('Running WP CLI commands...');
        $this->executor->execute('cli version');
        $this->io->writeComment(str_repeat('-', 69));
        $fileCommands = [];
        if ($this->files) {
            $fileCommands = array_filter(array_map([$this, 'evalFileCommand'], $this->files));
        }

        $commands = array_merge($fileCommands, $this->commands);

        $this->io->writeIfVerbose('Commands to run:');
        array_walk(
            $commands,
            function (string $command) {
                $this->io->writeIfVerbose("  `$ wp {$command}`");
            }
        );

        $this->io->write('starting now...');
        array_walk($this->commands, [$this->executor, 'execute']);
        $this->io->writeComment(str_repeat('-', 69));

        return self::SUCCESS;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return 'Error running WP CLI commands.';
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return '  <comment>WP CLI commands executed.</comment>';
    }

    /**
     * @param WpCli\FileData $fileData
     * @param Paths $paths
     * @return string
     */
    private function evalFileCommand(WpCli\FileData $fileData, Paths $paths): string
    {
        $fullpath = $paths->root($fileData->file());
        if (!file_exists($fullpath)) {
            $this->io->write("  '{$fullpath}' not found, can't eval with WP CLI.");

            return '';
        }

        $command = "eval-file {$fullpath}";
        $fileData->args() and $command .= ' ' . implode(' ', $fileData->args());
        $fileData->skipWordpress() and $command .= ' --skip-wordpress';

        return $command;
    }
}
