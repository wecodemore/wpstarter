<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;

/**
 * Helper around Composer remote filesystem to download files from arbitrary URL an either place
 * them in a given path or simply get the content.
 */
class UrlDownloader
{
    /**
     * @var \Composer\Util\RemoteFilesystem
     */
    private $remoteFilesystem;

    /**
     * @var \Composer\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var bool
     */
    private $isVerbose;

    /**
     * @param Filesystem $filesystem
     * @param RemoteFilesystem $remoteFilesystem
     */
    public function __construct(
        Filesystem $filesystem,
        RemoteFilesystem $remoteFilesystem,
        bool $isVerbose
    ) {

        $this->remoteFilesystem = $remoteFilesystem;
        $this->filesystem = $filesystem;
        $this->isVerbose = $isVerbose;
    }

    /**
     * Download an URL and save content to a file.
     *
     * @param string $url
     * @param string $filename
     * @return bool
     */
    public function save(string $url, string $filename): bool
    {
        $this->error = '';

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error = "Invalid URL {$url}.";

            return false;
        }

        $directory = dirname($filename);
        if (!$directory) {
            $this->error = "Invalid target path to download {$url}.";

            return false;
        }

        try {
            $this->filesystem->ensureDirectoryExists($directory);

            return $this->remoteFilesystem->copy(
                parse_url($url, PHP_URL_HOST),
                $url,
                $filename,
                $this->isVerbose
            );
        } catch (\Throwable $exception) {
            $this->error = $exception->getMessage();
        }

        return false;
    }

    /**
     * Perform a remote request and return the response as string.
     *
     * @param string $url
     * @return string
     */
    public function fetch(string $url): string
    {
        $this->error = '';

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error = "Invalid URL {$url}.";

            return '';
        }

        try {
            $host = parse_url($url, PHP_URL_HOST);
            $contents = $this->remoteFilesystem->getContents($host, $url, $this->isVerbose);
            if (!$contents || !is_string($contents)) {
                return '';
            }

            return $contents;
        } catch (\Throwable $exception) {
            $this->error = $exception->getMessage();

            return '';
        }
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return $this->error;
    }
}
