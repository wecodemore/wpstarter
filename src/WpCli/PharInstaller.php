<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\WpCli;

use WeCodeMore\WpStarter\Util\Io;
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
     * @param Command $command
     * @param string|null $path
     * @return string
     */
    public function install(Command $command, string $path = null): string
    {
        $url = $command->pharUrl();
        $name = $command->niceName();

        $path or $path = getcwd() . '/' . basename($url);

        if (!$this->urlDownloader->save($url, $path) || !file_exists($path)) {
            $this->io->error(sprintf('Failed to download %s phar from %s.', $name, $url));
            $this->io->error($this->urlDownloader->error());

            return '';
        }

        $postInstall = $command->postPharChecker();

        if ($postInstall && !$postInstall($path, $this->io)) {
            return '';
        }

        @chmod($path, 0550);

        $this->io->ok("{$name} installed successfully.");

        return $path;
    }
}
