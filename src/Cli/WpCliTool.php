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
        $ver = $this->minVersion();

        return $this->downloadEnabled
            ? "https://github.com/wp-cli/wp-cli/releases/download/v{$ver}/wp-cli-{$ver}.phar"
            : '';
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
        return '2.4.0';
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
