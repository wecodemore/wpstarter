<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup;

use Composer\Factory;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class UrlDownloader
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var \Composer\Util\RemoteFilesystem|null
     */
    private $remoteFilesystem;

    /**
     * @var \Composer\Util\Filesystem|null
     */
    private $filesystem;

    /**
     * @var bool|string
     */
    private $error = false;

    /**
     * Constructor. Validate and store the given url or an error if url is not valid.
     *
     * @param string                  $url
     * @param \WCM\WPStarter\Setup\IO $io
     */
    public function __construct($url, IO $io)
    {
        $composerIo = $io->composerIo();
        $config = Factory::createConfig($composerIo);

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (empty($scheme)) {
            $url = $config->get('disable-tls')
                ? 'http://'.ltrim($url, '/')
                : 'https://'.ltrim($url, '/');
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $this->url = $url;
        } else {
            $this->error = is_string($url) ? "{$url} is an invalid url." : "Invalid url.";
        }

        if ($this->url) {
            $this->remoteFilesystem = Factory::createRemoteFilesystem($composerIo, $config);
            $this->filesystem = new Filesystem();
        }
    }

    /**
     * Download an URL and save content to a file.
     *
     * @param  string $filename
     * @return bool
     */
    public function save($filename)
    {
        if (! $this->check()) {
            return false;
        }

        if (! is_string($filename) || ! dirname($filename)) {
            $this->error = "Invalid target path to download {$this->url}.";

            return false;
        }

        try {
            $this->filesystem->ensureDirectoryExists(dirname($filename));

            return $this->remoteFilesystem->copy(
                parse_url($this->url, PHP_URL_HOST),
                $this->url,
                $filename
            );
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        return false;
    }

    /**
     * Getter for the error.
     *
     * @return bool|string
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * Perform a remote request and return the response as string.
     *
     * @return string
     */
    public function fetch()
    {
        if (! $this->check()) {
            return '';
        }

        try {
            return $this->remoteFilesystem->getContents(
                parse_url($this->url, PHP_URL_HOST),
                $this->url
            );
        } catch (\Exception $e) {
            $this->error = $e->getMessage();

            return '';
        }
    }

    /**
     * @return bool
     */
    private function check()
    {
        return
            ! empty($this->url)
            && empty($this->error)
            && $this->remoteFilesystem instanceof RemoteFilesystem;
    }
}
