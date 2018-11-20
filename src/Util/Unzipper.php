<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        /**
         * We create this anonymous class extending ZipDownloader because its extract* methods
         * we are interested in are protected, and the public `extract()` method does not return.
         */
        $this->unzipper = $this->createUnzipper($io, $config, Platform::isWindows());
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
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->unzipper->unzip($zipPath, $target);
    }

    /**
     * @param IOInterface $io
     * @param Config $config
     * @param bool $isWindows
     * @return ZipDownloader
     *
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    private function createUnzipper(IOInterface $io, Config $config, bool $isWindows): ZipDownloader
    {
        // phpcs:enable

        return new class($io, $config, $isWindows) extends ZipDownloader
        {
            /**
             * @var bool
             */
            private $isWindows;

            /**
             * @param IOInterface $io
             * @param Config $config
             * @param bool $isWindows
             */
            public function __construct(IOInterface $io, Config $config, bool $isWindows)
            {
                $this->isWindows = $isWindows;
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
                    return $this->isWindows
                        ? $this->extractWithZipArchive($zipPath, $target, false)
                        : $this->extractWithSystemUnzip($zipPath, $target, false);
                } catch (\Throwable $exception) {
                    return false;
                }
            }
        };
    }
}
