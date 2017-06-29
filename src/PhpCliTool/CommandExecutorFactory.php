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

use Composer\Composer;
use WeCodeMore\WpStarter\Utils\Config;
use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\Paths;


/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WeCodeMore\WpStarter
 * @license http://opensource.org/licenses/MIT MIT
 */
class CommandExecutorFactory
{

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var IO
     */
    private $io;

    /**
     * @var Config
     */
    private $config;
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @param Paths $paths
     * @param IO $io
     * @param Config $config
     * @param Composer $composer
     */
    public function __construct(Paths $paths, IO $io, Config $config, Composer $composer)
    {
        $this->paths = $paths;
        $this->io = $io;
        $this->config = $config;
        $this->composer = $composer;
    }


    /**
     * @param ToolInterface $tool
     * @return null|CommandExecutor
     */
    public function create(ToolInterface $tool)
    {
        $fsPath = $this->lookForPackage($tool);

        // Installed via Composer, build executor and return
        if ($fsPath) {

            return new CommandExecutor($fsPath, $this->paths, $this->io, $tool);
        }

        $pharUrl = $tool->pharUrl();

        // If not installed via Composer and phar download is disabled, return nothing
        if (!$pharUrl) {

            $this->io->error(
                sprintf(
                    'Failed installation for %s: phar download URL is invalid or phar download is disabled',
                    $tool->niceName()
                )
            );

            return null;
        }

        $installedPath = (new PharInstaller($this->io))->install($tool);
        // Phar installation was successfull, build executor and return
        if ($installedPath) {
            return new CommandExecutor($installedPath, $this->paths, $this->io, $tool);
        }

        return null;
    }

    /**
     * Go through installed packages to find WP CLI.
     *
     * @param ToolInterface $tool
     * @return bool
     */
    private function lookForPackage(ToolInterface $tool)
    {
        /** @var \Composer\Package\PackageInterface[] $packages */
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $installationManager = $this->composer->getInstallationManager();

        foreach ($packages as $package) {

            if ($package->getName() !== $tool->packageName()) {
                continue;
            }

            $version = $package->getVersion();
            $minVersion = $tool->minVersion();

            if ($minVersion && version_compare($version, $minVersion, '<')) {
                $this->io->error(
                    sprintf(
                        'Installed %s version %s is lower than minimimun required %s.',
                        $tool->niceName(),
                        $version,
                        $minVersion
                    )
                );

                return '';
            }

            return $tool->executableFile($installationManager->getInstallPath($package));
        }

        return '';
    }
}