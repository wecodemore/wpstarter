<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\WpCli;

use Composer\Installer\InstallationManager;
use Composer\Repository\RepositoryInterface;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;

class ExecutorFactory
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var PharInstaller
     */
    private $pharInstaller;

    /**
     * @var RepositoryInterface
     */
    private $packageRepo;

    /**
     * @var InstallationManager
     */
    private $installationManager;

    /**
     * @param Locator $locator
     * @param RepositoryInterface $packageRepo
     * @param InstallationManager $installationManager
     */
    public function __construct(
        Locator $locator,
        RepositoryInterface $packageRepo,
        InstallationManager $installationManager
    ) {

        $this->config = $locator->config();
        $this->paths = $locator->paths();
        $this->io = $locator->io();
        $this->pharInstaller = $locator->pharInstaller();
        $this->packageRepo = $packageRepo;
        $this->installationManager = $installationManager;
    }

    /**
     * @param Command $command
     * @param string $phpPath
     * @return null|Executor
     */
    public function create(Command $command, string $phpPath)
    {
        $fsPath = $this->lookForPackage($command);

        // Installed via Composer, build executor and return
        if ($fsPath) {
            return new Executor($phpPath, $fsPath, $this->paths, $this->io, $command);
        }

        $targetPath = $command->pharTarget($this->paths);
        if (file_exists($targetPath)) {
            return new Executor($phpPath, $targetPath, $this->paths, $this->io, $command);
        }

        $pharUrl = $command->pharUrl();

        // If not installed via Composer and phar download is disabled, return nothing
        if (!$pharUrl) {
            $this->io->writeError(
                sprintf(
                    'Failed installation for %s: '
                    . 'phar download URL is invalid or phar download is disabled',
                    $command->niceName()
                )
            );

            return null;
        }

        $installedPath = $this->pharInstaller->install($command, $targetPath);

        // Phar installation was successfull, build executor and return
        if ($installedPath) {
            return new Executor($phpPath, $installedPath, $this->paths, $this->io, $command);
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
        $packages = $this->packageRepo->getPackages();

        foreach ($packages as $package) {
            if ($package->getName() !== $command->packageName()) {
                continue;
            }

            $version = $package->getVersion();
            $minVersion = $command->minVersion();

            if ($minVersion && version_compare($version, $minVersion, '<')) {
                $this->io->writeError(
                    sprintf(
                        'Installed %s version %s is lower than minimimun required %s.',
                        $command->niceName(),
                        $version,
                        $minVersion
                    )
                );

                return '';
            }

            return $command->executableFile($this->installationManager->getInstallPath($package));
        }

        return '';
    }
}
