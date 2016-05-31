<?php
/*
 * This file is part of the wpstarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package wpstarter
 */
class Filesystem
{
    /**
     * Save some textual content ro a file in given path.
     *
     * @param string $content
     * @param string $targetPath
     * @return bool
     */
    public function save($content, $targetPath)
    {
        $parent = dirname($targetPath);

        if (! $this->createDir($parent)) {
            return false;
        }

        try {
            return file_put_contents($targetPath, $content) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Move a single file from a sorce to a destination.
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function moveFile($sourcePath, $targetPath)
    {
        $targetParent = dirname($targetPath);

        if (! is_file($sourcePath) || ! $this->createDir($targetParent)) {
            return false;
        }

        try {
            return rename($sourcePath, $targetPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Copy a single file from a sorce to a destination.
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function copyFile($sourcePath, $targetPath)
    {
        $targetParent = dirname($targetPath);

        if (! is_file($sourcePath) || ! $this->createDir($targetParent)) {
            return false;
        }

        try {
            return copy($sourcePath, $targetPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Just a wrapper arouns symlink().
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function symlink($sourcePath, $targetPath)
    {
        try {
            return symlink($sourcePath, $targetPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Recursively copy all files from a directory to another.
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function moveDir($sourcePath, $targetPath)
    {
        $dir = new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        $done = $total = 0;
        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {
            $total++;
            $fullpathTarget = $targetPath.'/'.$item->getBasename();
            $done += $item->isDir()
                ? $this->createDir($fullpathTarget)
                : $this->moveFile($item->getPathname(), $fullpathTarget);
        }

        return $done === $total;
    }

    /**
     * Recursively copy all files from a directory to another.
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function copyDir($sourcePath, $targetPath)
    {
        $dir = new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        $done = $total = 0;
        foreach ($iterator as $item) {
            $total++;
            $fullpathTarget = $targetPath.'/'.$item->getBasename();
            $done += $item->isDir()
                ? $this->createDir($fullpathTarget)
                : $this->copyFile($item->getPathname(), $fullpathTarget);
        }

        return $done === $total;
    }

    /**
     * Create a directory recusrively, derived from wp_makedir_p.
     *
     * @param string $targetPath
     * @return bool
     */
    public function createDir($targetPath)
    {
        $targetPath = rtrim(str_replace('\\', '/', $targetPath), '/');
        $targetPath or $targetPath = '/';

        if (file_exists($targetPath)) {
            return @is_dir($targetPath);
        }

        $parentDir = dirname($targetPath);
        while ('.' != $parentDir && ! is_dir($parentDir)) {
            $parentDir = dirname($parentDir);
        }

        if ($stat = @stat($parentDir)) {
            $permissions = $stat['mode'] & 0007777;
        } else {
            $permissions = 0755;
        }

        if (@mkdir($targetPath, $permissions, true)) {
            if ($permissions != ($permissions & ~umask())) {
                $nameParts = explode('/', substr($targetPath, strlen($parentDir) + 1));
                for ($i = 1, $count = count($nameParts); $i <= $count; $i++) {
                    $dirname = $parentDir.'/'.implode('/', array_slice($nameParts, 0, $i));
                    @chmod($dirname, $permissions);
                }
            }

            return true;
        }

        return false;
    }
}
