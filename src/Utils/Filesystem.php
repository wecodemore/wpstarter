<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Utils;

use Composer\Util\Filesystem as FilesystemUtil;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
class Filesystem
{
    /**
     * @var \Composer\Util\Filesystem
     */
    private $util;

    public function __construct()
    {
        $this->util = new FilesystemUtil();
    }

    /**
     * Save some textual content to a file in given path.
     *
     * @param string $content
     * @param string $targetPath
     * @return bool
     */
    public function save($content, $targetPath)
    {
        $parent = dirname($this->util->normalizePath($targetPath));

        if (!$this->createDir($parent)) {
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
     * @throws \RuntimeException
     */
    public function moveFile($sourcePath, $targetPath)
    {
        file_exists($targetPath) and $this->util->unlink($targetPath);

        $this->util->rename(
            $this->util->normalizePath($sourcePath),
            $this->util->normalizePath($targetPath)
        );

        return file_exists($targetPath);
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
        $sourcePath = realpath($sourcePath);

        if (!is_file($sourcePath) || !$this->createDir(dirname($targetPath))) {
            return false;
        }

        try {
            return copy($sourcePath, $targetPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Just a wrapper around symlink().
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function symlink($sourcePath, $targetPath)
    {
        try {
            return @symlink($sourcePath, $targetPath);
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
        try {
            $sourcePath = $this->util->normalizePath($sourcePath);
            if (!realpath($sourcePath)) {
                return false;
            }

            $this->util->copyThenRemove(
                $sourcePath,
                $this->util->normalizePath($targetPath)
            );
        } catch (\Exception $e) {
            return false;
        }

        return is_dir($targetPath) && !is_dir($targetPath);
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
        $sourcePath = $this->util->normalizePath($sourcePath);
        if (!realpath($sourcePath)) {
            return false;
        }

        $targetPath = $this->util->normalizePath($targetPath);
        $this->createDir($targetPath);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $done = $total = 0;

        foreach ($iterator as $item) {
            $total++;
            /** @var \RecursiveDirectoryIterator $iterator */
            $done += $item->isDir()
                ? (int)$this->createDir("{$targetPath}/" . $iterator->getSubPathName())
                : (int)$this->copyFile($item, "{$targetPath}/" . $iterator->getSubPathName());
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
        $targetPath = $this->util->normalizePath($targetPath) ?: '/';

        if (file_exists($targetPath)) {
            return @is_dir($targetPath);
        }

        $parentDir = dirname($targetPath);
        while ('.' !== $parentDir && !is_dir($parentDir)) {
            $parentDir = dirname($parentDir);
        }

        $stat = @stat($parentDir);
        $permissions = $stat ? $stat['mode'] & 0007777 : 0755;

        if (!@mkdir($targetPath, $permissions, true) && !is_dir($targetPath)) {
            return false;
        }

        if ($permissions !== ($permissions & ~umask())) {
            $nameParts = explode('/', substr($targetPath, strlen($parentDir) + 1));
            for ($i = 1, $count = count($nameParts); $i <= $count; $i++) {
                $dirname = $parentDir . '/' . implode('/', array_slice($nameParts, 0, $i));
                @chmod($dirname, $permissions);
            }
        }

        return true;
    }

    /**
     * @return FilesystemUtil
     */
    public function composerFilesystem()
    {
        return $this->util;
    }
}
