<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the WpStarter package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Robo;

use Robo;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use WeCodeMore\WpStarter\PhpCliTool\ToolInterface;
use WeCodeMore\WpStarter\Utils\Config;
use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\Paths;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WpStarter
 * @license http://opensource.org/licenses/MIT MIT
 */
class Tool implements ToolInterface
{

    /**
     * @var bool
     */
    private $pharDownloadEnabled = false;

    /**
     * @var string
     */
    private $roboConfigFile;

    /**
     * @param Config $config
     * @param Paths $paths
     */
    public function __construct(Config $config, Paths $paths)
    {
        $this->pharDownloadEnabled = (bool)$config[Config::INSTALL_ROBO];

        $roboConfig = $config[Config::ROBO_CONFIG];
        if (is_string($roboConfig) && file_exists($roboConfig)) {
            $ext = strtolower(pathinfo($roboConfig, PATHINFO_EXTENSION));
            in_array($ext, ['yml', 'yaml'], true) and $this->roboConfigFile = $roboConfig;
        } elseif (file_exists($paths->root('robo.yml'))) {
            $this->roboConfigFile = $paths->root('robo.yml');
        }

    }

    /**
     * @return string
     */
    public function niceName()
    {
        return 'Robo';
    }

    /**
     * @return string
     */
    public function packageName()
    {
        return 'consolidation/robo';
    }

    /**
     * @return string
     */
    public function pharUrl()
    {
        return $this->pharDownloadEnabled
            ? 'http://robo.li/robo.phar'
            : '';
    }

    /**
     * @param Paths $paths
     * @return string
     */
    public function pharTarget(Paths $paths)
    {
        return $this->pharDownloadEnabled
            ? $paths->root('robo.phar')
            : '';
    }

    /**
     * @param $packageVendorPath
     * @return string
     */
    public function executableFile($packageVendorPath)
    {
        return rtrim($packageVendorPath, '\\/') . '/robo.php';
    }

    /**
     * @return string
     */
    public function minVersion()
    {
        return '1.0.0';
    }

    /**
     * @return callable
     */
    public function postPharChecker()
    {
        return function ($pharPath, IO $io) {

            require_once "phar://{$pharPath}/vendor/autoload.php";

            return true;
        };
    }

    /**
     * @param string $command
     * @param Paths $paths
     * @param IO $io
     * @return string
     * @throws Exception
     */
    public function prepareCommand($command, Paths $paths, IO $io)
    {
        if (!file_exists($command)) {
            return '';
        }

        if (!class_exists(Robo\Runner::ROBOCLASS)) {
            require_once $command;
        }

        $status = 0;

        if (class_exists(Robo\Runner::ROBOCLASS)) {

            $runner = new Robo\Runner();
            /** @var Robo\Application $app */
            list($app, $container, $output) = $this->buildRoboData();
            $runner->setContainer($container);
            $status = $runner->execute([], $app->getName(), $app->getVersion(), $output);
        }

        if ($status > 0) {

            $message = "Robo taks execution failed";
            $error = error_get_last();
            $message .= is_array($error) && !empty($error['message'])
                ? ": {$error['message']}."
                : ".";

            throw new Exception($message);
        }

        return '';
    }

    private function buildRoboData()
    {
        $configFiles = $this->roboConfigFile ? [$this->roboConfigFile] : [];

        $input = new StringInput('');
        $output = new ConsoleOutput();
        $app = Robo\Robo::application();
        $config = Robo\Robo::createConfiguration($configFiles);
        $container = Robo\Robo::createDefaultContainer($input, $output, $app, $config);

        return [$app, $container, $output];
    }
}