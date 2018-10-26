<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Cli;

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
     * @param PhpTool $info
     * @param string|null $path
     * @return string
     */
    public function install(PhpTool $info, string $path): string
    {
        $url = $info->pharUrl();
        $name = $info->niceName();

        $this->io->write(sprintf('Installing %s...', $name));
        if (!$this->urlDownloader->save($url, $path) || !file_exists($path)) {
            $this->io->writeError(sprintf('Failed to download %s phar from %s.', $name, $url));
            $this->io->writeError($this->urlDownloader->error());

            return '';
        }

        if (!$info->checkPhar($path, $this->io)) {
            @unlink($path);
            $this->io->writeError('Phar validation failed. Downloaded phar is probably corrupted.');

            return '';
        }

        @chmod($path, 0550);

        $this->io->writeSuccess("{$name} installed successfully.");

        return $path;
    }
}
