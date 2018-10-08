<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\WpCli;

use Composer\Composer;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\UrlDownloader;

class ExecutorFactory
{
    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var UrlDownloader
     */
    private $urlDownloader;

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
     * @param Io $io
     * @param UrlDownloader $urlDownloader
     * @param Config $config
     * @param Composer $composer
     */
    public function __construct(
        Paths $paths,
        Io $io,
        UrlDownloader $urlDownloader,
        Config $config,
        Composer $composer
    ) {

        $this->paths = $paths;
        $this->io = $io;
        $this->urlDownloader = $urlDownloader;
        $this->config = $config;
        $this->composer = $composer;
    }

    /**
     * @param Command $command
     * @return null|Executor
     */
    public function create(Command $command)
    {
        $fsPath = $this->lookForPackage($command);

        // Installed via Composer, build executor and return
        if ($fsPath) {
            return new Executor($fsPath, $this->paths, $this->io, $command);
        }

        $pharUrl = $command->pharUrl();

        // If not installed via Composer and phar download is disabled, return nothing
        if (!$pharUrl) {
            $this->io->error(
                sprintf(
                    'Failed installation for %s: '
                    . 'phar download URL is invalid or phar download is disabled',
                    $command->niceName()
                )
            );

            return null;
        }

        $installedPath = (new PharInstaller($this->io, $this->urlDownloader))->install($command);
        // Phar installation was successfull, build executor and return
        if ($installedPath) {
            return new Executor($installedPath, $this->paths, $this->io, $command);
        }

        return null;
    }

    /**
     * Go through installed packages to find WP CLI.
     *
     * @param Command $command
     * @return string
     */
    private function lookForPackage(Command $command): string
    {
        /** @var \Composer\Package\PackageInterface[] $packages */
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $installationManager = $this->composer->getInstallationManager();

        foreach ($packages as $package) {
            if ($package->getName() !== $command->packageName()) {
                continue;
            }

            $version = $package->getVersion();
            $minVersion = $command->minVersion();

            if ($minVersion && version_compare($version, $minVersion, '<')) {
                $this->io->error(
                    sprintf(
                        'Installed %s version %s is lower than minimimun required %s.',
                        $command->niceName(),
                        $version,
                        $minVersion
                    )
                );

                return '';
            }

            return $command->executableFile($installationManager->getInstallPath($package));
        }

        return '';
    }
}
