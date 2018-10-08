<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;

class UrlDownloader
{
    /**
     * @var \Composer\Util\RemoteFilesystem|null
     */
    private $remoteFilesystem;

    /**
     * @var \Composer\Util\Filesystem|null
     */
    private $filesystem;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @param Filesystem $filesystem
     * @param RemoteFilesystem $remoteFilesystem
     */
    public function __construct(Filesystem $filesystem, RemoteFilesystem $remoteFilesystem)
    {
        $this->remoteFilesystem = $remoteFilesystem;
        $this->filesystem = $filesystem;
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

        if (!dirname($filename)) {
            $this->error = "Invalid target path to download {$url}.";

            return false;
        }

        try {
            $this->filesystem->ensureDirectoryExists(dirname($filename));

            return $this->remoteFilesystem->copy(
                parse_url($url, PHP_URL_HOST),
                $url,
                $filename
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
            return $this->remoteFilesystem->getContents(parse_url($url, PHP_URL_HOST), $url);
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
