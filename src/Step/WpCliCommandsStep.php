<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Cli;

/**
 * A step that runs WP CLI commands set in WP Starter configuration.
 *
 * WP Starter accepts a configuration with a series of WP CLI commands to be run.
 * The benefit of using WP Starter for this task is that WP Starter will download WP CLI phar if
 * necessary (only if WP CLI is not installed via Composer as package, by also verifying its
 * integrity via hash) and will direct the commands to the phar or to the binary without having
 * to worry about it.
 */
final class WpCliCommandsStep implements ConditionalStep
{
    public const NAME = 'wpcli';

    /**
     * @var Locator
     */
    private $locator;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var Cli\PhpToolProcess|null
     */
    private $process = null;

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->locator = $locator;
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
        [$commands, $files] = $this->extractConfig($config);

        return $commands || $files;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        [$commands, $files] = $this->extractConfig($config);

        $this->io->write('');
        $this->io->writeComment("Running WP CLI commands...");

        $checkVer = $this->io->isVerbose()
            ? $this->process()->execute('cli version')
            : $this->process()->executeSilently('cli version');

        $this->io->write('');

        if (!$checkVer) {
            return self::ERROR;
        }

        $fileCommands = [];
        if ($files) {
            foreach ($files as $file) {
                $command = $this->buildEvalFileCommand($file, $paths);
                $command and $fileCommands[] = $command;
            }
        }

        $commands = array_merge($fileCommands, $commands);
        $this->initMessage(...$commands);

        $continue = true;
        while ($continue && $commands) {
            $command = array_shift($commands);
            $commandDesc = $this->commandDesc($command);
            $dashes = str_repeat('-', 54 - strlen($commandDesc));
            $this->io->write("<fg=magenta>\$ wp {$commandDesc} {$dashes}</>");
            $continue = $this->process()->execute($command);
            if (!$continue) {
                $this->io->writeError("'wp {$command}' FAILED! Quitting WP CLI.");
            }
            $this->io->write('<fg=magenta>' . str_repeat('-', 60) . '</>');
            $this->io->write('');

            usleep(200000);
        }

        return $continue ? self::SUCCESS : self::ERROR;
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
     * @return string
     */
    public function conditionsNotMet(): string
    {
        return 'no WP CLI commands  to run found';
    }

    /**
     * @param Config $config
     * @return array{list<string>, list<Cli\WpCliFileData>}
     */
    private function extractConfig(Config $config): array
    {
        global $locator;
        $locator = $this->locator;
        /** @var list<string> $commands */
        $commands = $config[Config::WP_CLI_COMMANDS]->unwrap();
        unset($GLOBALS['locator']);
        /** @var list<Cli\WpCliFileData> $files */
        $files = $config[Config::WP_CLI_FILES]->unwrap();

        return [$commands, $files];
    }

    /**
     * @return Cli\PhpToolProcess
     */
    private function process(): Cli\PhpToolProcess
    {
        $this->process or $this->process = $this->locator->wpCliProcess();

        return $this->process;
    }

    /**
     * @param Cli\WpCliFileData $fileData
     * @param Paths $paths
     * @return string
     */
    private function buildEvalFileCommand(Cli\WpCliFileData $fileData, Paths $paths): string
    {
        $fullpath = $paths->root($fileData->file());
        if (!file_exists($fullpath)) {
            $this->io->write("  '{$fullpath}' not found, can't eval with WP CLI.");

            return '';
        }

        $command = "eval-file {$fullpath}";
        /** @var array<string> $args */
        $args = $fileData->args();
        $args and $command .= ' ' . implode(' ', $args);
        $fileData->skipWordpress() and $command .= ' --skip-wordpress';

        return $command;
    }

    /**
     * @param string ...$commands
     * @return void
     */
    private function initMessage(string ...$commands): void
    {
        $count = count($commands);
        $this->io->writeIfVerbose(sprintf('Will run %d command%s:', $count, $count > 1 ? 's' : ''));

        array_walk(
            $commands,
            function (string $command, int $i): void {
                $num = $i + 1;
                $commandDesc = ltrim($this->commandDesc("  {$command}"));
                $this->io->writeIfVerbose("  <comment>{$num}) \$ wp {$commandDesc}</comment>");
            }
        );

        $this->io->writeIfVerbose('');
    }

    /**
     * @param string $command
     * @return string
     */
    private function commandDesc(string $command): string
    {
        if (strlen($command) <= 51) {
            return $command;
        }

        return (substr($command, 0, 48) ?: '') . '...';
    }
}
