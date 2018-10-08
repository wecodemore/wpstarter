<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Composer;
use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;

class MuPluginList
{

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @param Composer $composer
     */
    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    /**
     * @return array
     */
    public function pluginsList(): array
    {
        $list = [];

        /** @var \Composer\Package\PackageInterface[] $packages */
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $installationManager = $this->composer->getInstallationManager();
        foreach ($packages as $package) {
            if ($package->getType() === 'wordpress-muplugin') {
                $path = $this->pathForPluginPackage($installationManager, $package);
                $path and $list[$package->getName()] = $path;
            }
        }

        return $list;
    }

    /**
     * @param InstallationManager $installationManager
     * @param PackageInterface $package
     * @return string
     */
    private function pathForPluginPackage(
        InstallationManager $installationManager,
        PackageInterface $package
    ): string {

        $path = $installationManager->getInstallPath($package);
        if (!$path) {
            return '';
        }

        $files = glob("{$path}/*.php");
        if (!$files) {
            return '';
        }

        if (count($files) === 1) {
            return reset($files);
        }

        foreach ($files as $file) {
            if ($this->isPluginFile($file)) {
                return $file;
            }
        }

        return '';
    }

    /**
     * @param string $file
     * @return bool
     */
    private function isPluginFile(string $file): bool
    {
        $handle = @fopen($file, 'r');
        $data = @fread($handle, 8192);
        @fclose($handle);
        if (!$data) {
            return false;
        }

        $data = str_replace("\r", "\n", $data);

        return preg_match('/^[ \t\/*#@]*Plugin Name:(.*)$/mi', $data, $match) && ! empty($match[1]);
    }
}
