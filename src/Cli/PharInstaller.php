<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Cli;

use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\UrlDownloader;

class PharInstaller
{
    /**
     * @var Io
     */
    private $io;

    /**
     * @var UrlDownloader
     */
    private $urlDownloader;

    /**
     * @param Io $io
     * @param UrlDownloader $urlDownloader
     */
    public function __construct(Io $io, UrlDownloader $urlDownloader)
    {
        $this->io = $io;
        $this->urlDownloader = $urlDownloader;
    }

    /**
     * @param PhpTool $info
     * @param string $path
     * @return string
     */
    public function install(PhpTool $info, string $path): string
    {
        $url = $info->pharUrl();
        $name = $info->niceName();

        if (!$url || !$name) {
            $this->io->write(
                sprintf(
                    "Skipping installation of PHP tool '%s'.\nName: %s, URL: %s.",
                    get_class($info),
                    $name ? "'{$name}'" : '(empty)',
                    $url ? "'{$url}'" : '(empty)'
                )
            );

            return '';
        }

        $this->io->write(sprintf('Installing %s...', $name));
        if (!$this->urlDownloader->save($url, $path) || !file_exists($path)) {
            $this->io->writeErrorBlock(sprintf('Failed to download %s phar from %s.', $name, $url));
            $this->io->writeErrorBlock($this->urlDownloader->error());

            return '';
        }

        if (!$info->checkPhar($path, $this->io)) {
            @unlink($path);
            $this->io->writeErrorBlock('Phar validation failed. Downloaded phar seems corrupted.');

            return '';
        }

        @chmod($path, 0550); // phpcs:ignore

        $this->io->writeSuccess("{$name} installed successfully.");

        return $path;
    }
}
