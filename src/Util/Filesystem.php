<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Util\Platform;

/**
 * Wrapper for Composer Filesystem with custom functionalities.
 *
 * @method string normalizePath(string $path)
 * @method string findShortestPath(string $from, string $to, bool $bothDirs = false)
 */
class Filesystem
{
    public const OP_AUTO = 'auto';
    public const OP_COPY = 'copy';
    public const OP_SYMLINK = 'symlink';
    public const OP_NONE = 'none';
    public const OPERATIONS = [self::OP_AUTO, self::OP_COPY, self::OP_SYMLINK, self::OP_NONE];

    /**
     * @var ComposerFilesystem
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
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments = [])
    {
        if (!method_exists($this->filesystem, $name)) {
            throw new \Error(sprintf('Call to undefined method %s::%s()', __CLASS__, $name));
        }

        return $this->filesystem->{$name}(...$arguments);
    }

    /**
     * Save some textual content to a file in given path.
     *
     * @param string $content
     * @param string $targetPath
     * @return bool
     */
    public function writeContent(string $content, string $targetPath): bool
    {
        try {
            $parent = dirname($this->filesystem->normalizePath($targetPath));

            if (!$this->createDir($parent)) {
                return false;
            }

            $exists = file_exists($targetPath);
            if ($exists && !is_file($targetPath)) {
                return false;
            }

            $currentContent = $exists ? file_get_contents($targetPath) : null;
            if ($currentContent === $content) {
                return true;
            }

            return file_put_contents($targetPath, $content) !== false;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * @param string $content
     * @param string $targetPath
     * @return bool
     *
     * @deprecated
     */
    public function save(string $content, string $targetPath): bool
    {
        return $this->writeContent($content, $targetPath);
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
        return $this->copyOrMoveFile($sourcePath, $targetPath, false);
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
        return $this->copyOrMoveFile($sourcePath, $targetPath, true);
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
        return $this->copyOrMoveDir($sourcePath, $targetPath, false);
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
        return $this->copyOrMoveDir($sourcePath, $targetPath, true);
    }

    /**
     * Symlink implementation which uses junction on dirs on Windows.
     *
     * @param string $targetPath
     * @param string $linkPath
     * @return bool
     */
    public function symlink(string $targetPath, string $linkPath): bool
    {
        try {
            if (!file_exists($targetPath)) {
                return false;
            }

            if (file_exists($linkPath) || $this->isLink($linkPath)) {
                $this->filesystem->unlink($linkPath);
            }

            $isWindows = Platform::isWindows();
            $directories = is_dir($targetPath);

            if ($isWindows && $directories) {
                $this->filesystem->junction($targetPath, $linkPath);

                return $this->filesystem->isJunction($linkPath);
            }

            $absolute = $this->filesystem->isAbsolutePath($targetPath)
                && $this->filesystem->isAbsolutePath($linkPath);

            // Attempt relative symlink, but not on Windows
            if ($absolute && !$isWindows) {
                return $this->filesystem->relativeSymlink($targetPath, $linkPath);
            }

            return @symlink($targetPath, $linkPath);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * @param string $sourcePath
     * @param string $targetPath
     * @return bool
     */
    public function symlinkOrCopy(string $sourcePath, string $targetPath): bool
    {
        if ($this->symlink($sourcePath, $targetPath)) {
            return true;
        }

        $realpath = realpath($sourcePath);
        if (!$realpath) {
            return false;
        }

        return is_dir($realpath)
            ? $this->copyDir($realpath, $targetPath)
            : $this->copyFile($realpath, $targetPath);
    }

    /**
     * @param string $source
     * @param string $target
     * @param string $operation
     * @return bool
     */
    public function symlinkOrCopyOperation(string $source, string $target, string $operation): bool
    {
        if (!in_array($operation, self::OPERATIONS, true) || ($operation === self::OP_NONE)) {
            return false;
        }

        try {
            switch ($operation) {
                case Filesystem::OP_COPY:
                    return is_file($source)
                        ? $this->copyFile($source, $target)
                        : $this->copyDir($source, $target);
                case Filesystem::OP_SYMLINK:
                    return $this->symlink($source, $target);
                default:
                    return $this->symlinkOrCopy($source, $target);
            }
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * Create a directory recursively, derived from wp_makedir_p.
     *
     * @param string $targetPath
     * @return bool
     */
    public function createDir(string $targetPath): bool
    {
        try {
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
                $nameParts = explode('/', substr($targetPath, strlen($parentDir) + 1) ?: '');
                for ($i = 1, $count = count($nameParts); $i <= $count; $i++) {
                    $dirname = $parentDir . '/' . implode('/', array_slice($nameParts, 0, $i));
                    @chmod($dirname, $permissions);
                }
            }

            return true;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * Remove a directory only if "real".
     *
     * @param string $directory
     * @return bool
     */
    public function removeRealDir(string $directory): bool
    {
        try {
            if ($this->isLink($directory)) {
                return false;
            }

            if (is_dir($directory)) {
                return $this->filesystem->removeDirectory($directory);
            }

            return !file_exists($directory);
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isLink(string $path): bool
    {
        return $this->filesystem->isSymlinkedDirectory($path)
            || $this->filesystem->isJunction($path)
            || is_link($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function unlinkOrRemove(string $path): bool
    {
        try {
            if ($this->isLink($path)) {
                return $this->filesystem->unlink($path);
            }

            return !file_exists($path) || $this->filesystem->remove($path);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * @param string $sourcePath
     * @param string $targetPath
     * @param bool $copy
     * @return bool
     */
    private function copyOrMoveFile(string $sourcePath, string $targetPath, bool $copy): bool
    {
        try {
            $sourcePath = realpath($sourcePath);
            if (!$sourcePath || !is_file($sourcePath)) {
                return false;
            }

            $targetPath = $this->filesystem->normalizePath($targetPath);
            if (!$this->createDir(dirname($targetPath))) {
                return false;
            }

            $this->unlinkOrRemove($targetPath);

            $copy
                ? copy($sourcePath, $targetPath)
                : $this->filesystem->rename($sourcePath, $targetPath);

            return file_exists($targetPath);
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * @param string $sourcePath
     * @param string $targetPath
     * @param bool $copy
     * @return bool
     */
    private function copyOrMoveDir(string $sourcePath, string $targetPath, bool $copy): bool
    {
        try {
            $sourcePath = realpath($sourcePath);
            if (!$sourcePath || !is_dir($sourcePath)) {
                return false;
            }

            $targetPath = $this->filesystem->normalizePath($targetPath);
            $exists = file_exists($targetPath);
            if ($exists && !is_dir($targetPath)) {
                return false;
            }

            if (!$exists && !$this->createDir($targetPath)) {
                return false;
            }

            $copy
                ? $this->filesystem->copy($sourcePath, $targetPath)
                : $this->filesystem->copyThenRemove($sourcePath, $targetPath);

            if (!is_dir($targetPath)) {
                return false;
            }

            return $copy || !file_exists($sourcePath);
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
