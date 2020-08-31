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
        $this->unzipper = new ZipDownloader($io, $config);
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
        try {
            $this->unzipper->extract($zipPath, $target);

            return true;
        } catch (\Throwable $throwable) {
            return false;
        }
    }
}
