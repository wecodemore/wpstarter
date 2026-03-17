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

/**
 * Helper around Composer remote filesystem to download files from arbitrary URL and either place
 * them in a given path or simply get the content.
 */
class UrlDownloader
{
    private HttpDownloader $httpDownloader;
    private Filesystem $filesystem;
    private string $error = '';

    /**
     * @param HttpDownloader $httpDownloader
     * @param Filesystem $filesystem
     * @return UrlDownloader
     *
     * @deprecated use UrlDownloader::new
     */
    public static function newV2(
        HttpDownloader $httpDownloader,
        Filesystem $filesystem
    ): UrlDownloader {

        return self::new($httpDownloader, $filesystem);
    }

    /**
     * @param HttpDownloader $httpDownloader
     * @param Filesystem $filesystem
     * @return UrlDownloader
     */
    public static function new(
        HttpDownloader $httpDownloader,
        Filesystem $filesystem
    ): UrlDownloader {

        return new self($httpDownloader, $filesystem);
    }

    /**
     * @param HttpDownloader $httpDownloader
     * @param Filesystem $filesystem
     */
    private function __construct(HttpDownloader $httpDownloader, Filesystem $filesystem)
    {
        $this->httpDownloader = $httpDownloader;
        $this->filesystem = $filesystem;
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

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $this->error = "Invalid URL {$url}.";

            return false;
        }

        $directory = dirname($filename);
        if ($directory === '') {
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

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
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

        $response = $this->httpDownloader->get($url);
        $statusCode = $response->getStatusCode();
        if ($statusCode > 199 && $statusCode < 300) {
            $result = $response->getBody();
        }

        if (!is_string($result) || ($result === '')) {
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
        $response = $this->httpDownloader->copy($url, $filename);
        $statusCode = $response->getStatusCode();

        return $statusCode > 199 && $statusCode < 300;
    }
}
