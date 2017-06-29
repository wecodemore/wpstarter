<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Utils\Config;
use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\Paths;
use WeCodeMore\WpStarter\PhpCliTool\CommandExecutor;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class WpCliCommandsStep implements StepInterface
{

    const NAME = 'wp-cli-commands';

    /**
     * @var array
     */
    private $commands = [];

    /**
     * @var IO
     */
    private $io;

    /**
     * @var CommandExecutor
     */
    private $executor;

    /**
     * @param IO $io
     */
    public function __construct(IO $io)
    {
        $this->io = $io;
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return self::NAME;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function allowed(Config $config, Paths $paths)
    {
        if ($config[Config::WP_CLI_EXECUTOR] instanceof CommandExecutor) {
            $this->executor = $config[Config::WP_CLI_EXECUTOR];
            $this->commands = $config[Config::WP_CLI_COMMANDS];

            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function run(Paths $paths, $verbosity)
    {
        if (!$this->commands) {
            return self::NONE;
        }

        $this->io->comment('Running WP CLI commands...');
        $this->executor->execute('cli version');
        $this->io->comment(str_repeat('-', 69));
        if ($verbosity > 1) {
            $this->io->write('Commands to run:');
            array_walk($this->commands, function($comment) {
                $this->io->write("  `$ wp {$comment}`");
            });
            $this->io->write('starting now...');
        }
        array_walk($this->commands, [$this->executor, 'execute']);
        $this->io->comment(str_repeat('-', 69));

        return self::SUCCESS;
    }

    /**
     * @inheritdoc
     */
    public function error()
    {
        return 'Error running WP CLI commands.';
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        return '  <comment>WP CLI commands executed.</comment>';
    }
}
