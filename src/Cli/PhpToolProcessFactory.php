<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Cli;

use Symfony\Component\Process\PhpExecutableFinder;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\PackageFinder;
use WeCodeMore\WpStarter\Util\Paths;

class PhpToolProcessFactory
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
     * @var PharInstaller
     */
    private $pharInstaller;

    /**
     * @var PackageFinder
     */
    private $packageFinder;

    /**
     * @var PhpProcess
     */
    private $process;

    /**
     * @param Paths $paths
     * @param Io $io
     * @param PharInstaller $pharInstaller
     * @param PackageFinder $packageFinder
     * @param PhpProcess $process
     */
    public function __construct(
        Paths $paths,
        Io $io,
        PharInstaller $pharInstaller,
        PackageFinder $packageFinder,
        PhpProcess $process
    ) {

        $this->paths = $paths;
        $this->io = $io;
        $this->pharInstaller = $pharInstaller;
        $this->packageFinder = $packageFinder;
        $this->process = $process;
    }

    /**
     * @param PhpTool $command
     * @param string|null $phpPath
     * @return PhpToolProcess
     */
    public function create(PhpTool $command, ?string $phpPath = null): PhpToolProcess
    {
        ($phpPath === null) and $phpPath = (new PhpExecutableFinder())->find();
        if (!$phpPath) {
            throw new \RuntimeException(
                sprintf(
                    'Failed installation for %s: PHP executable not found.',
                    $command->niceName()
                )
            );
        }

        $fsPath = $this->lookForPackage($command);

        // Installed via Composer, build executor and return
        if ($fsPath) {
            return new PhpToolProcess($this->process, $command, $fsPath, $this->paths, $this->io);
        }

        $targetPath = $command->pharTarget($this->paths);

        if ($targetPath && file_exists($targetPath)) {
            return new PhpToolProcess($this->process, $command, $targetPath, $this->paths, $this->io);
        }

        $pharUrl = $command->pharUrl();

        // If not installed via Composer and phar download is disabled, return nothing
        if (!$pharUrl) {
            throw new \RuntimeException(
                sprintf(
                    'Failed installation for %s: '
                    . 'phar download URL is invalid or phar download is disabled.',
                    $command->niceName()
                )
            );
        }

        $installedPath = $this->pharInstaller->install($command, $targetPath);

        if (!$installedPath) {
            throw new \RuntimeException("Failed phar download from {$pharUrl}.");
        }

        return new PhpToolProcess($this->process, $command, $installedPath, $this->paths, $this->io);
    }

    /**
     * Go through installed packages to find WP CLI.
     *
     * @param PhpTool $command
     * @return string
     */
    private function lookForPackage(PhpTool $command): string
    {
        $package = $this->packageFinder->findByName($command->packageName());

        if (!$package) {
            return '';
        }

        $version = $package->getVersion();
        $minVersion = $command->minVersion();

        if ($minVersion && version_compare($version, $minVersion, '<')) {
            $this->io->writeErrorBlock(
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

        return $path ? $command->filesystemBootstrap($path) : '';
    }
}
