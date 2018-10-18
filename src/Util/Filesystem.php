<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Util\Platform;

/**
 * Wrapper for Composer Filesystem with custom functionalities.
 */
class Filesystem
{
    /**
     * @var \Composer\Util\Filesystem
     */
    private $filesystem;

    /**
     * @param ComposerFilesystem $filesystem
     */
    public function __construct(ComposerFilesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Save some textual content to a file in given path.
     *
     * @param string $content
     * @param string $targetPath
     * @return bool
     */
    public function save(string $content, string $targetPath): bool
    {
        $parent = dirname($this->filesystem->normalizePath($targetPath));

        if (!$this->createDir($parent)) {
            return false;
        }

        try {
            return file_put_contents($targetPath, $content) > 0;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Move a single file from a source to a destination.
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function moveFile(string $sourcePath, string $targetPath): bool
    {
        file_exists($targetPath) and $this->filesystem->unlink($targetPath);

        $this->filesystem->rename(
            $this->filesystem->normalizePath($sourcePath),
            $this->filesystem->normalizePath($targetPath)
        );

        return file_exists($targetPath);
    }

    /**
     * Copy a single file from a source to a destination.
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function copyFile(string $sourcePath, string $targetPath): bool
    {
        $sourcePath = realpath($sourcePath);

        if (!is_file($sourcePath) || !$this->createDir(dirname($targetPath))) {
            return false;
        }

        try {
            return copy($sourcePath, $targetPath);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * Symlink implementation which uses junction on dirs on Windows.
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function symlink(string $sourcePath, string $targetPath): bool
    {
        try {
            if (Platform::isWindows() && is_dir($sourcePath) && is_dir($targetPath)) {
                $this->filesystem->junction($targetPath, $sourcePath);
            }

            if ($this->filesystem->isAbsolutePath($sourcePath)
                && $this->filesystem->isAbsolutePath($targetPath)
            ) {
                $this->filesystem->relativeSymlink($targetPath, $sourcePath);
            }

            return @symlink($sourcePath, $targetPath);
        } catch (\Throwable $exception) {
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
    public function moveDir(string $sourcePath, string $targetPath): bool
    {
        try {
            $sourcePath = $this->filesystem->normalizePath($sourcePath);
            if (!realpath($sourcePath)) {
                return false;
            }

            $this->filesystem->copyThenRemove(
                $sourcePath,
                $this->filesystem->normalizePath($targetPath)
            );
        } catch (\Throwable $exception) {
            return false;
        }

        return is_dir($targetPath) && !is_dir($sourcePath);
    }

    /**
     * Recursively copy all files from a directory to another.
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function copyDir(string $sourcePath, string $targetPath): bool
    {
        $sourcePath = $this->filesystem->normalizePath($sourcePath);
        if (!realpath($sourcePath)) {
            return false;
        }

        $targetPath = $this->filesystem->normalizePath($targetPath);
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
     * Create a directory recursively, derived from wp_makedir_p.
     *
     * @param string $targetPath
     * @return bool
     */
    public function createDir(string $targetPath): bool
    {
        $targetPath = $this->filesystem->normalizePath($targetPath);

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
     * Remove a directory.
     *
     * @param string $directory
     * @return bool
     */
    public function removeRealDir(string $directory): bool
    {
        if ($this->filesystem->isSymlinkedDirectory($directory)
            || $this->filesystem->isJunction($directory)
            || is_link($directory)
        ) {
            return false;
        }

        if (is_dir($directory)) {
            return $this->filesystem->removeDirectory($directory);
        }

        return true;
    }
}
