<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\WpCli;

use Composer\Repository\RepositoryInterface;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\PackageFinder;
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
    private $packageFinder;

    /**
     * @param Config $config
     * @param Paths $paths
     * @param Io $io
     * @param PharInstaller $pharInstaller
     * @param PackageFinder $packageFinder
     */
    public function __construct(
        Config $config,
        Paths $paths,
        Io $io,
        PharInstaller $pharInstaller,
        PackageFinder $packageFinder
    ) {

        $this->config = $config;
        $this->paths = $paths;
        $this->io = $io;
        $this->pharInstaller = $pharInstaller;
        $this->packageFinder = $packageFinder;
    }

    /**
     * @param Command $command
     * @param string $phpPath
     * @return Executor
     */
    public function create(Command $command, string $phpPath): Executor
    {
        $fsPath = $this->lookForPackage($command);

        // Installed via Composer, build executor and return
        if ($fsPath) {
            return new Executor($phpPath, $fsPath, $this->paths, $this->io, $command);
        }

        $targetPath = $command->pharTarget($this->paths);
        if ($targetPath && file_exists($targetPath)) {
            return new Executor($phpPath, $targetPath, $this->paths, $this->io, $command);
        }

        $pharUrl = $command->pharUrl();

        // If not installed via Composer and phar download is disabled, return nothing
        if (!$pharUrl) {
            throw new \RuntimeException(
                sprintf(
                    'Failed installation for %s: '
                    . 'phar download URL is invalid or phar download is disabled',
                    $command->niceName()
                )
            );
        }

        $installedPath = $this->pharInstaller->install($command, $targetPath);

        if (!$installedPath) {
            throw new \RuntimeException("Failed phar download from {$pharUrl}.");
        }

        return new Executor($phpPath, $installedPath, $this->paths, $this->io, $command);
    }

    /**
     * Go through installed packages to find WP CLI.
     *
     * @param Command $command
     * @return string
     */
    private function lookForPackage(Command $command): string
    {
        $package = $this->packageFinder->findByName($command->packageName());
        if (!$package) {
            return '';
        }

        $version = $package->getVersion();
        $minVersion = $command->minVersion();

        if ($minVersion && version_compare($version, $minVersion, '<')) {
            $this->io->writeError(
                sprintf(
                    'Installed %s version %s is lower than minimum required %s.',
                    $command->niceName(),
                    $version,
                    $minVersion
                )
            );

            return '';
        }

        $path = $this->packageFinder->findPathOf($package);

        return $path ? $command->executableFile($path) : '';
    }
}
