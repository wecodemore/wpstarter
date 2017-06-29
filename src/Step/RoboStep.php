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

use WeCodeMore\WpStarter\Robo\Exception;
use WeCodeMore\WpStarter\Utils\Config;
use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\Paths;
use WeCodeMore\WpStarter\PhpCliTool\CommandExecutor;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class RoboStep implements StepInterface
{

    const NAME = 'robo-tasks';

    /**
     * @var IO
     */
    private $io;

    /**
     * @var CommandExecutor
     */
    private $executor;

    /**
     * @var string
     */
    private $roboFile;

    private $error = 'Error running Robo tasks.';

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
        if (!$config[Config::WP_CLI_EXECUTOR] instanceof CommandExecutor) {
            return false;
        }

        $roboFile = $config[Config::ROBO_FILE];

        if (is_string($roboFile) && file_exists($roboFile)) {
            $ext = strtolower(pathinfo($roboFile, PATHINFO_EXTENSION));
            $ext === 'php' and $this->roboFile = $roboFile;
        } elseif (file_exists($paths->root('RoboFile.php'))) {
            $this->roboFile = $paths->root('RoboFile.php');
        }

        if (!$this->roboFile) {
            return false;
        }

        $this->executor = $config[Config::WP_CLI_EXECUTOR];

        return false;
    }

    /**
     * @inheritdoc
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function run(Paths $paths, $verbosity)
    {
        if (!$this->roboFile || ! $this->executor) {
            return self::NONE;
        }

        $this->io->comment('Running Robo tasks...');
        $this->io->comment(str_repeat('-', 69));

        if ($verbosity > 1) {
            $this->io->write("Tasks will be loaded from RoboFile at {$this->roboFile}");
        }

        try {
            $this->executor->execute($this->roboFile);
            $this->io->comment(str_repeat('-', 69));
        } catch (Exception $e) {
            $this->error = $e->getMessage();

            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * @inheritdoc
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        return "  <comment>Robo tasks from {$this->roboFile} executed.</comment>";
    }
}
