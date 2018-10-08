<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\WpCli;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\UrlDownloader;

class Command
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
     * @param Config $config
     * @param UrlDownloader $urlDownloader
     */
    public function __construct(Config $config, UrlDownloader $urlDownloader)
    {
        $this->downloadEnabled = (bool)$config[Config::INSTALL_WP_CLI]->unwrapOrFallback(false);
        $this->urlDownloader = $urlDownloader;
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
        return $this->downloadEnabled
            ? 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar'
            : '';
    }

    /**
     * @param Paths $paths
     * @return string
     */
    public function pharTarget(Paths $paths): string
    {
        return $this->downloadEnabled
            ? $paths->root('wp.phar')
            : '';
    }

    /**
     * @param $packageVendorPath
     * @return string
     */
    public function executableFile(string $packageVendorPath): string
    {
        return rtrim($packageVendorPath, '\\/') . '/php/boot-fs.php';
    }

    /**
     * @return string
     */
    public function minVersion(): string
    {
        return '1.2.1';
    }

    /**
     * @return callable
     */
    public function postPharChecker(): callable
    {
        return function (string $pharPath, Io $io): bool {

            list($algo, $hashUrl) = $this->hashAlgoUrl($io);

            $hash = $this->urlDownloader->fetch($hashUrl);

            if (!$hash) {
                $io->error("Failed to download {$algo} hash from {$hashUrl}.");
                $io->error($this->urlDownloader->error());

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
     * @return string
     */
    public function prepareCommand(string $command, Paths $paths): string
    {
        if (strpos($command, 'wp ') === 0) {
            $command = substr($command, 3);
        }

        return "{$command} --path=" . $paths->wp();
    }

    /**
     * @param Io $io
     * @return array
     */
    private function hashAlgoUrl(Io $io): array
    {
        if (in_array('sha512', hash_algos(), true)) {
            return ['sha512', $this->pharUrl() . '.sha512'];
        }

        $io->comment(
            'NOTICE: SHA-512 algorithm is not available on the system,'
            . ' WP Starter will use the less secure MD5 to check WP CLI phar integrity.'
        );

        return ['md5', $this->pharUrl() . '.md5'];
    }
}
