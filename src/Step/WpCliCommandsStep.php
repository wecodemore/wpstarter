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

/**
 * A step that runs WP CLI commands set in WP Starter configuration.
 *
 * WP Starter accepts a configuration with a series of WP CLI commands to be run.
 * The benefit of using WP Starter for this tasks is that WP Starter will download WP CLI phar if
 * necessary (only if WP CLI is not installed via Composer as package, by also verifying its
 * integrity via hash) and will direct the commands to the phar or to the binary without having
 * to worry about it.
 */
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
        $this->executor = $locator->wpCliExecutor();
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
        $commands = $config[Config::WP_CLI_COMMANDS]->unwrapOrFallback([]);
        $files = $config[Config::WP_CLI_FILES]->unwrapOrFallback([]);

        if ($commands || $files) {
            $this->commands = $commands;
            $this->files = $files;

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
        if ((!$this->commands && !$this->files)) {
            return self::NONE;
        }

        $this->io->writeComment('Running WP CLI commands...');
        $this->executor->execute('cli version');
        $fileCommands = [];
        if ($this->files) {
            $fileCommands = array_filter(array_map([$this, 'buildEvalFileCommand'], $this->files));
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
    private function buildEvalFileCommand(WpCli\FileData $fileData, Paths $paths): string
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
