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
use Composer\Util\Filesystem as ComposerFilesystem;
use Symfony\Component\Finder\Finder;
use WeCodeMore\WpStarter\Config\Config;

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
     * @var ComposerFilesystem
     */
    private $filesystem;

    /**
     * @var list<string>|null
     */
    private $dropins = null;

    /**
     * @param PackageFinder $packageFinder
     * @param Paths $paths
     * @param ComposerFilesystem $filesystem
     */
    public function __construct(
        PackageFinder $packageFinder,
        Paths $paths,
        ComposerFilesystem $filesystem
    ) {

        $this->packageFinder = $packageFinder;
        $this->paths = $paths;
        $this->filesystem = $filesystem;
    }

    /**
     * @param Config $config
     * @return array<string, string>
     */
    public function pluginsList(Config $config): array
    {
        $list = [];

        // First, we search for packages with "wordpress-muplugin" type.
        // We store each path we have looked into in an array we'll use below.
        // PHP files found in "wordpress-muplugin" packages' root will need the use "Plugin name"
        // header to have them loaded, unless a single file is found in path.
        $packagesPaths = [];
        $packages = $this->packageFinder->findByType('wordpress-muplugin');
        foreach ($packages as $package) {
            $paths = $this->pathsForPluginPackage($package);
            if (!$paths) {
                continue;
            }

            $name = $package->getName();
            $multi = count($paths) > 1;
            foreach ($paths as $path) {
                $packagesPaths[] = dirname($path);
                $key = $multi ? "{$name}_" . pathinfo($path, PATHINFO_FILENAME) : $name;
                $list[$key] = $path;
            }
        }

        // Now we check any subdirectory of MU plugins folder, which we haven't looked into yet
        // (that why we store the array above), to see if there's any MU plugin to load in there.
        // This is necessary because MU plugins in the wp.org repository don't have the
        // "wordpress-muplugin" type, because wp.org doesn't allow it.
        // So we can move them to the MU plugin folder via installers path, but we then need to have
        // them loaded.
        // Because we have no indication files are actually plugins, we require the plugin header to
        // be there even if a single PHP file is in a path.
        $muPluginsDir = $this->paths->wpContent('/mu-plugins/');
        if (!is_dir($muPluginsDir)) {
            return $list;
        }

        $muPluginsSubDirs = Finder::create()
            ->in($muPluginsDir)
            ->depth(0)
            ->directories()
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true);

        foreach ($muPluginsSubDirs as $muSubDir) {
            $muDirPath = $this->filesystem->normalizePath($muSubDir->getPathname());
            if (in_array($muDirPath, $packagesPaths, true)) {
                continue;
            }
            $morePaths = $this->mupluginsPathsInDir($muDirPath, true, $config);
            $name = basename($muDirPath);
            $multi = count($morePaths) > 1;
            foreach ($morePaths as $path) {
                $key = $multi ? "{$name}_" . pathinfo($path, PATHINFO_FILENAME) : $name;
                $list[$key] = $path;
            }
        }

        return $list;
    }

    /**
     * @param PackageInterface $package
     * @return list<string>
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

        return $this->mupluginsPathsInDir($path, false);
    }

    /**
     * @param string $path
     * @param bool $requireHeader
     * @param Config|null $config
     * @return list<string>
     */
    private function mupluginsPathsInDir(
        string $path,
        bool $requireHeader,
        ?Config $config = null
    ): array {

        $files = Finder::create()->in($path)
            ->name('*.php')
            ->depth(0)
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true)
            ->ignoreDotFiles(true)
            ->files();
        $count = $files->count();
        if ($count === 0) {
            return [];
        }

        // If a folder contains a single file, and we know the folder is for a MU plugin, then
        // we can assume that file is the file to load without checking plugin header.
        // A header is required when we don't know for sure the path belongs to a MU plugin package.
        $single = ($count === 1) && !$requireHeader;

        $paths = [];
        foreach ($files as $file) {
            if (!$file->isReadable()) {
                continue;
            }
            $path = $this->filesystem->normalizePath($file->getRealPath());
            if ($config && $this->isDropinPath($path, $config)) {
                continue;
            }
            if ($single || $this->isPluginFile($path)) {
                $paths[] = $path;
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

    /**
     * @param string $path
     * @param Config $config
     * @return bool
     */
    private function isDropinPath(string $path, Config $config): bool
    {
        $realpath = $this->filesystem->normalizePath(realpath($path));

        return in_array($realpath, $this->dropinsList($config), true);
    }

    /**
     * @param Config $config
     * @return list<string>
     *
     * @psalm-assert list<string> $this->dropins
     */
    private function dropinsList(Config $config): array
    {
        if (is_array($this->dropins)) {
            return $this->dropins;
        }

        $this->dropins = [];
        /** @var array<string, string> $dropins */
        $dropins = $config[Config::DROPINS]->unwrapOrFallback([]);
        foreach ($dropins as $dropin) {
            if (filter_var($dropin, FILTER_VALIDATE_URL)) {
                continue;
            }
            $dropinPath = realpath($dropin);
            $dropinPath and $this->dropins[] = $this->filesystem->normalizePath($dropinPath);
        }

        return $this->dropins;
    }
}
