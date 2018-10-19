<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Util\Filesystem as ComposerFilesystem;

class PackageFinder
{
    /**
     * @var RepositoryInterface
     */
    private $packageRepo;

    /**
     * @var InstallationManager
     */
    private $installationManager;

    /**
     * @var ComposerFilesystem
     */
    private $filesystem;

    /**
     * @var PackageInterface[]
     */
    private $packages;

    /**
     * @param RepositoryInterface $packageRepo
     * @param InstallationManager $installationManager
     * @param ComposerFilesystem $filesystem
     */
    public function __construct(
        RepositoryInterface $packageRepo,
        InstallationManager $installationManager,
        ComposerFilesystem $filesystem
    ) {

        $this->packageRepo = $packageRepo;
        $this->installationManager = $installationManager;
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $type
     * @return PackageInterface[]
     */
    public function findByType(string $type): array
    {
        $list = [];
        $packages = $this->all();

        foreach ($packages as $package) {
            if ($package->getType() === $type) {
                $list[] = $package;
            }
        }

        return $list;
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    public function findPathOf(PackageInterface $package): string
    {
        $path = $this->installationManager->getInstallPath($package);

        return $this->filesystem->normalizePath($path);
    }

    /**
     * @param string $vendor
     * @return PackageInterface[]
     */
    public function findByVendor(string $vendor): array
    {
        $list = [];
        $packages = $this->all();

        $vendor = rtrim($vendor, '/') . '/';

        foreach ($packages as $package) {
            if (stripos($package->getPrettyName(), $vendor) === 0
                || stripos($package->getName(), $vendor) === 0
            ) {
                $list[] = $package;
                continue;
            }
        }

        return $list;
    }

    /**
     * @param string $name
     * @return PackageInterface|null
     */
    public function findByName(string $name)
    {
        $packages = $this->all();

        foreach ($packages as $package) {
            if ($package->getPrettyName() === $name || $package->getName() === $name) {
                return $package;
            }
        }

        return null;
    }

    /**
     * @return PackageInterface[]
     */
    private function all(): array
    {
        if (!is_array($this->packages)) {
            $this->packages = $this->packageRepo->getPackages();
        }

        return $this->packages;
    }
}
