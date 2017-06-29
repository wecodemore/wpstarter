<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the WpStarter package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\WpCli;

use WeCodeMore\WpStarter\PhpCliTool\ToolInterface;
use WeCodeMore\WpStarter\Utils\Config;
use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\Paths;
use WeCodeMore\WpStarter\Utils\UrlDownloader;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WpStarter
 * @license http://opensource.org/licenses/MIT MIT
 */
class Tool implements ToolInterface
{

    /**
     * @var bool
     */
    private $pharDownloadEnabled = false;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->pharDownloadEnabled = (bool) $config[Config::INSTALL_WP_CLI];
    }

    /**
     * @return string
     */
    public function niceName()
    {
        return 'WP CLI';
    }

    /**
     * @return string
     */
    public function packageName()
    {
        return 'wp-cli/wp-cli';
    }

    /**
     * @return string
     */
    public function pharUrl()
    {
        return $this->pharDownloadEnabled
            ? 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar'
            : '';
    }

    /**
     * @param Paths $paths
     * @return string
     */
    public function pharTarget(Paths $paths)
    {
        return $this->pharDownloadEnabled
            ? $paths->root('wp.phar')
            : '';
    }

    /**
     * @param $packageVendorPath
     * @return string
     */
    public function executableFile($packageVendorPath)
    {
        return rtrim($packageVendorPath, '\\/') . '/php/boot-fs.php';
    }

    /**
     * @return string
     */
    public function minVersion()
    {
        return '1.2.1';
    }

    /**
     * @return callable
     */
    public function postPharChecker()
    {
        return function ($pharPath, IO $io) {

            list($algo, $hashUrl) = $this->hashAlgoUrl($io);
            $hashDownloader = new UrlDownloader($hashUrl, $io);

            $hash = $hashDownloader->fetch();

            if (!$hash) {
                $io->error("Failed to download {$algo} hash from {$hashUrl}.");
                $io->error($hashDownloader->error());

                return false;
            }

            if (hash($algo, file_get_contents($pharPath)) !== $hash) {
                $io->error("{$algo} hash check failed for downloaded WP CLI phar.");

                return false;
            }

            return true;
        };
    }

    /**
     * @param $command
     * @param $paths
     * @param $io
     * @return mixed
     */
    public function prepareCommand($command, Paths $paths, IO $io)
    {
        if (strpos($command, 'wp ') === 0) {
            $command = substr($command, 3);
        }

        return "{$command} --path=" . $paths->wp();
    }

    /**
     * @param IO $io
     * @return array
     */
    private function hashAlgoUrl(IO $io)
    {
        $algo = hash_algos();
        if (in_array('sha512', $algo, true)) {
            return ['sha512', $this->pharUrl() . '.sha512'];
        }

        $io->comment(
            'NOTICE: sha512 is not available on the system, will use less secure MD5 to check WP CLI phar integrity.'
        );

        return ['md5', $this->pharUrl() . '.md5'];
    }
}