<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use Composer\Config;
use Composer\Downloader\ZipDownloader;
use Composer\IO\IOInterface;
use Composer\Util\Platform;

/**
 * Wrapper around Composer ZipDownloader because we need just an unzipper, not a downloader.
 */
class Unzipper
{
    /**
     * @var ZipDownloader
     */
    private $unzipper;

    /**
     * @param IOInterface $io
     * @param Config $config
     */
    public function __construct(IOInterface $io, Config $config)
    {
        $this->unzipper = $this->createUnzipper($io, $config);
    }

    /**
     * Unzip a given zip file to given target path.
     *
     * @param string $zipPath
     * @param string $target
     * @return bool
     */
    public function unzip(string $zipPath, string $target): bool
    {
        /** @var callable(string, string): bool */
        $unzip = [$this->unzipper, 'unzip'];

        return $unzip($zipPath, $target);
    }

    /**
     * We create this anonymous class extending ZipDownloader because its extract* methods
     * we are interested in are protected, and the public `extract()` method does not return.
     *
     * @param IOInterface $io
     * @param Config $config
     * @return ZipDownloader
     *
     * phpcs:disable Inpsyde.CodeQuality.NestingLevel
     */
    private function createUnzipper(IOInterface $io, Config $config): ZipDownloader
    {
        // phpcs:enable Inpsyde.CodeQuality.NestingLevel

        return new class ($io, $config) extends ZipDownloader
        {
            /**
             * @var bool
             */
            private $useZipArchive;

            /**
             * @param IOInterface $io
             * @param Config $config
             */
            public function __construct(IOInterface $io, Config $config)
            {
                $this->useZipArchive = Platform::isWindows();
                parent::__construct($io, $config);
            }

            /**
             * @param string $zipPath
             * @param string $target
             * @return bool
             */
            public function unzip(string $zipPath, string $target): bool
            {
                try {
                    return $this->useZipArchive
                        ? $this->extractWithZipArchive($zipPath, $target, false)
                        : $this->extractWithSystemUnzip($zipPath, $target, false);
                } catch (\Throwable $exception) {
                    return false;
                }
            }
        };
    }
}
