<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Cli;

use Composer\Semver\Semver;
use Symfony\Component\Finder\Finder;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\UrlDownloader;

class WpCliTool implements PhpTool
{
    private const API_URL = 'https://api.github.com/repos/wp-cli/wp-cli/releases/latest';
    private const PHAR_URL_BASE = 'https://github.com/wp-cli/wp-cli/releases/download';
    private const PHAR_URL_FORMAT = self::PHAR_URL_BASE . '/v%1$s/wp-cli-%1$s.phar';
    private const PHAR_URL_REGEX = 'v[^/]+/wp\-cli\-[^/]+\.phar';

    /**
     * @var bool
     */
    private $downloadEnabled;

    /**
     * @var UrlDownloader
     */
    private $urlDownloader;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var string|null
     */
    private $pharUrl = null;

    /**
     * @param Config $config
     * @param UrlDownloader $urlDownloader
     * @param Io $io
     */
    public function __construct(Config $config, UrlDownloader $urlDownloader, Io $io)
    {
        $this->downloadEnabled = (bool)$config[Config::INSTALL_WP_CLI]->unwrapOrFallback(true);
        $this->urlDownloader = $urlDownloader;
        $this->io = $io;
    }

    /**
     * @return string
     */
    public function niceName(): string
    {
        return 'WP CLI';
    }

    /**
     * @return string
     */
    public function packageName(): string
    {
        return 'wp-cli/wp-cli';
    }

    /**
     * @return string
     */
    public function pharUrl(): string
    {
        if (!$this->downloadEnabled) {
            return '';
        }

        if ($this->pharUrl !== null) {
            return $this->pharUrl;
        }

        $min = $this->minVersion();
        $this->pharUrl = sprintf(self::PHAR_URL_FORMAT, $min);

        $found = null;

        try {
            $found = $this->findLatestReleaseUrl();
            $found
                ? ($this->pharUrl = $found)
                : $this->io->writeError('Could not find latest WP CLI version via GitHub API.');
        } catch (\Throwable $throwable) {
            $this->io->writeError('GitHub API call failed.');
            $this->io->writeError($throwable->getMessage());
        }

        $found or $this->io->writeComment("Will fallback to WP CLI '{$min}' version.");

        return $this->pharUrl;
    }

    /**
     * @param Paths $paths
     * @return string
     */
    public function pharTarget(Paths $paths): string
    {
        $default = $paths->root('/wp-cli.phar');
        if (file_exists($default)) {
            return $default;
        }

        $candidates = [];
        if (preg_match('~/wp-cli-(.+?)\.phar$~', $this->pharUrl(), $matches)) {
            $version = $matches[1];
            $path = $paths->root($matches[0]);
            if (file_exists($path)) {
                $candidates[$version] = $path;
            }
        }

        $existingFiles = Finder::create()
            ->in($paths->root('/'))
            ->depth('== 0')
            ->name('wp-cli-*.phar');

        $constraint = '>=' . $this->minVersion();

        /** @var \SplFileInfo $existingFile */
        foreach ($existingFiles as $existingFile) {
            $fileName = $existingFile->getBasename('.phar');
            $fullPath = $paths->root("/{$fileName}.phar");
            $version = (string)substr($fileName, 7);
            if (Semver::satisfies($version, $constraint)) {
                $candidates[$version] = $fullPath;
            }
        }

        if (!$candidates) {
            return $default;
        }

        $version = Semver::rsort(array_keys($candidates))[0];

        return $candidates[$version];
    }

    /**
     * @param string $packageVendorPath
     * @return string
     */
    public function filesystemBootstrap(string $packageVendorPath): string
    {
        return rtrim($packageVendorPath, '\\/') . '/php/boot-fs.php';
    }

    /**
     * @return string
     */
    public function minVersion(): string
    {
        return '2.5.0';
    }

    /**
     * @param string $pharPath
     * @param Io $io
     * @return bool
     */
    public function checkPhar(string $pharPath, Io $io): bool
    {
        list($algorithm, $hashUrl) = $this->hashAlgorithmUrl($io);

        $this->io->write(sprintf('Checking %s via %s hash...', $this->niceName(), $algorithm));
        $releaseHash = trim($this->urlDownloader->fetch($hashUrl));
        if (!$releaseHash) {
            $io->writeErrorBlock("Failed to download {$algorithm} hash content from {$hashUrl}.");
            $io->writeErrorBlock($this->urlDownloader->error());

            return false;
        }

        $pharHash = hash($algorithm, (string)file_get_contents($pharPath));
        if (!$pharHash || !hash_equals($releaseHash, $pharHash)) {
            $io->writeErrorBlock("{$algorithm} hash check failed for downloaded WP CLI phar.");

            return false;
        }

        return true;
    }

    /**
     * @param string $command
     * @param Paths $paths
     * @param Io $io
     * @return string
     */
    public function prepareCommand(string $command, Paths $paths, Io $io): string
    {
        return "{$command} --path=" . $paths->wp();
    }

    /**
     * @return string|null
     */
    private function findLatestReleaseUrl(): ?string
    {
        $found = null;
        $json = $this->urlDownloader->fetch(self::API_URL);
        $data = @json_decode($json, true);
        if (!is_array($data) || !is_array($data['assets'] ?? null)) {
            $url = self::API_URL;
            throw new \Error("Failed downloading data from API URL '{$url}'.");
        }
        foreach ($data['assets'] as $asset) {
            if (!is_array($asset) || !is_string($asset['browser_download_url'] ?? null)) {
                continue;
            }
            /** @var string $url */
            $url = $asset['browser_download_url'];
            $regex = sprintf(
                '~^%s/%s$~',
                preg_quote(self::PHAR_URL_BASE, '~'),
                self::PHAR_URL_REGEX
            );
            if (preg_match($regex, $url) && filter_var($url, FILTER_VALIDATE_URL)) {
                /** @var string|false $sanitizesUrl */
                $sanitizesUrl = filter_var($url, FILTER_SANITIZE_URL);
                $sanitizesUrl and $found = $sanitizesUrl;
                break;
            }
        }

        return $found;
    }

    /**
     * @param Io $io
     * @return array{non-empty-string, non-empty-string}
     */
    private function hashAlgorithmUrl(Io $io): array
    {
        if (in_array('sha512', hash_algos(), true)) {
            return ['sha512', $this->pharUrl() . '.sha512'];
        }

        $io->writeIfVerbose('SHA512 not available, going to use MD5...');

        return ['md5', $this->pharUrl() . '.md5'];
    }
}
