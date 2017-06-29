<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\PhpCliTool;

use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\UrlDownloader;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WeCodeMore\WpStarter
 * @license http://opensource.org/licenses/MIT MIT
 */
class PharInstaller
{

    /**
     * @var IO
     */
    private $io;

    /**
     * @param IO $io
     */
    public function __construct(IO $io)
    {
        $this->io = $io;
    }

    /**
     * @param ToolInterface $tool
     * @param null $path
     * @return string
     */
    public function install(ToolInterface $tool, $path = null)
    {
        $url = $tool->pharUrl();
        $name = $tool->niceName();

        $downloader = new UrlDownloader($url, $this->io);

        $path or $path = getcwd() . '/' . basename($url);

        if (!$downloader->save($path) || !file_exists($path)) {
            $this->io->error(sprintf('Failed to download %s phar from %s.', $name, $url));
            $this->io->error($downloader->error());

            return '';
        }

        $postInstall = $tool->postPharChecker();

        if ($postInstall && !$postInstall($path, $this->io)) {
            return '';

        }

        @chmod($path, 0550);

        $this->io->ok("{$name} installed successfully.");

        return $path;
    }

}