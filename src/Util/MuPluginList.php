<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use Composer\Package\PackageInterface;

/**
 * Helper that uses Composer objects to get a list of installed packages and filter them to obtain
 * the list of installed MU plugins and their installation paths.
 */
class MuPluginList
{
    /**
     * @var PackageFinder
     */
    private $packageFinder;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @param PackageFinder $packageFinder
     * @param Paths $paths
     */
    public function __construct(PackageFinder $packageFinder, Paths $paths)
    {
        $this->packageFinder = $packageFinder;
        $this->paths = $paths;
    }

    /**
     * @return array<string, string>
     */
    public function pluginsList(): array
    {
        $list = [];

        $packages = $this->packageFinder->findByType('wordpress-muplugin');
        foreach ($packages as $package) {
            $paths = $this->pathsForPluginPackage($package);
            if (!$paths) {
                continue;
            }

            $name = $package->getName();
            $multi = count($paths) > 1;
            foreach ($paths as $path) {
                $key = $multi ? "{$name}_" . pathinfo($path, PATHINFO_FILENAME) : $name;
                $list[$key] = $path;
            }
        }

        return $list;
    }

    /**
     * @param PackageInterface $package
     * @return array<string>
     */
    private function pathsForPluginPackage(PackageInterface $package): array
    {
        $path = $this->packageFinder->findPathOf($package);
        if (!$path) {
            return [];
        }

        $root = $this->paths->root('/');
        if (strpos($path, $root) !== 0) {
            $path = $this->paths->root($path);
        }

        if (!file_exists($path)) {
            return [];
        }

        $files = glob("{$path}/*.php");
        if (!$files) {
            return [];
        }

        if (count($files) === 1) {
            $file = reset($files);

            return is_readable($file) ? [$file] : [];
        }

        $paths = [];
        foreach ($files as $file) {
            if (is_readable($file) && $this->isPluginFile($file)) {
                $paths[] = $file;
            }
        }

        return $paths;
    }

    /**
     * @param string $file
     * @return bool
     */
    private function isPluginFile(string $file): bool
    {
        $data = null;
        $handle = @fopen($file, 'r');
        if ($handle) {
            $data = @fread($handle, 8192);
            @fclose($handle);
        }

        if (!$data) {
            return false;
        }

        $data = str_replace("\r", "\n", $data);

        return preg_match('/^[ \t\/*#@]*Plugin Name:(.*)$/mi', $data, $match) && !empty($match[1]);
    }
}
