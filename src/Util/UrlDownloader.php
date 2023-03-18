<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use Composer\Util\HttpDownloader;
use Composer\Util\RemoteFilesystem;

/**
 * Helper around Composer remote filesystem to download files from arbitrary URL and either place
 * them in a given path or simply get the content.
 */
class UrlDownloader
{
    /**
     * @var HttpDownloader|null
     */
    private $httpDownloader;

    /**
     * @var RemoteFilesystem|null
     */
    private $remoteFilesystem;

    /**
     * @var Filesystem
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
     * @param HttpDownloader $httpDownloader
     * @param Filesystem $filesystem
     * @param bool $isVerbose
     * @return UrlDownloader
     */
    public static function newV2(
        \Composer\Util\HttpDownloader $httpDownloader,
        Filesystem $filesystem,
        bool $isVerbose
    ): UrlDownloader {

        $instance = new self($filesystem, $isVerbose);
        $instance->httpDownloader = $httpDownloader;

        return $instance;
    }

    /**
     * @param RemoteFilesystem $remoteFilesystem
     * @param Filesystem $filesystem
     * @param bool $isVerbose
     * @return UrlDownloader
     */
    public static function newV1(
        \Composer\Util\RemoteFilesystem $remoteFilesystem,
        Filesystem $filesystem,
        bool $isVerbose
    ): UrlDownloader {

        $instance = new self($filesystem, $isVerbose);
        $instance->remoteFilesystem = $remoteFilesystem;

        return $instance;
    }

    /**
     * @param Filesystem $filesystem
     * @param bool $isVerbose
     */
    private function __construct(Filesystem $filesystem, bool $isVerbose)
    {
        $this->filesystem = $filesystem;
        $this->isVerbose = $isVerbose;
    }

    /**
     * Download a URL and save content to a file.
     *
     * @param non-empty-string $url
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
            $this->filesystem->createDir($directory);
            $result = $this->copyUrl($url, $filename);
        } catch (\Throwable $exception) {
            $this->error = $exception->getMessage();
            $result = false;
        }

        return $result;
    }

    /**
     * Perform a remote request and return the response as string.
     *
     * @param non-empty-string $url
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
            return $this->retrieveContents($url);
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

    /**
     * @param non-empty-string $url
     * @return string
     */
    private function retrieveContents(string $url): string
    {
        $result = null;

        if ($this->remoteFilesystem) {
            /**
             * @noinspection PhpUndefinedMethodInspection
             * @psalm-suppress UndefinedMethod
             */
            $origin = (string)RemoteFilesystem::getOrigin($url);
            /** @psalm-suppress InternalMethod */
            $result = $this->remoteFilesystem->getContents($origin, $url, $this->isVerbose);
        } elseif ($this->httpDownloader) {
            $response = $this->httpDownloader->get($url);
            $statusCode = $response->getStatusCode();
            if ($statusCode > 199 && $statusCode < 300) {
                $result = $response->getBody();
            }
        }

        if (!$result || !is_string($result)) {
            throw new \Exception("Could not obtain a response from '{$url}'.");
        }

        return $result;
    }

    /**
     * @param non-empty-string $url
     * @param string $filename
     * @return bool
     */
    private function copyUrl(string $url, string $filename): bool
    {
        if ($this->remoteFilesystem) {
            /**
             * @noinspection PhpUndefinedMethodInspection
             * @psalm-suppress UndefinedMethod
             * @psalm-suppress InternalMethod
             */
            return (bool)$this->remoteFilesystem->copy(
                (string)RemoteFilesystem::getOrigin($url),
                $url,
                $filename,
                $this->isVerbose
            );
        } elseif ($this->httpDownloader) {
            $response = $this->httpDownloader->copy($url, $filename);
            $statusCode = $response->getStatusCode();

            return $statusCode > 199 && $statusCode < 300;
        }

        return false;
    }
}
