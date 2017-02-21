<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup\Steps;

use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\OverwriteHelper;
use WCM\WPStarter\Setup\UrlDownloader;
use ArrayAccess;
use Exception;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class DropinStep implements FileStepInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $actionSource;

    /**
     * @var \WCM\WPStarter\Setup\IO
     */
    private $io;

    /**
     * @var \WCM\WPStarter\Setup\OverwriteHelper
     */
    private $overwrite;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var string
     */
    private $success = '';

    /**
     * @param string                               $name
     * @param string                               $url
     * @param \WCM\WPStarter\Setup\IO              $io
     * @param \WCM\WPStarter\Setup\OverwriteHelper $overwrite
     */
    public function __construct($name, $url, IO $io, OverwriteHelper $overwrite)
    {
        $this->name = filter_var($name, FILTER_SANITIZE_URL);
        $this->url = $url;
        $this->io = $io;
        $this->overwrite = $overwrite;
    }

    /**
     * {@inheritdoc}
     */
    public function allowed(Config $config, ArrayAccess $paths)
    {
        $this->actionSource = $this->action($this->url, $paths);
        if (empty($this->actionSource[0])) {
            $this->io->error("{$this->url} is not a valid url nor a valid path.");

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function run(ArrayAccess $paths)
    {
        $dest = $this->targetPath($paths);
        if (!$this->overwrite->should($dest)) {
            $this->io->comment("  - {$this->name} skipped.");

            return;
        }
        $this->actionSource[0] === 'download'
            ? $this->download($this->actionSource[1], $dest)
            : $this->copy($this->actionSource[1], $dest, $paths);
    }

    /**
     * {@inheritdoc}
     */
    public function error()
    {
        return trim($this->error);
    }

    /**
     * {@inheritdoc}
     */
    public function success()
    {
        return trim($this->success);
    }

    /**
     * {@inheritdoc}
     */
    public function targetPath(ArrayAccess $paths)
    {
        return $paths['root'].'/'.$paths['wp-content'].'/'.$this->name;
    }

    /**
     * Download dropin file from given url and save it to in wp-content folder.
     *
     * @param string $url
     * @param string $dest
     */
    private function download($url, $dest)
    {
        $remote = new UrlDownloader($url);
        $name = basename($dest);
        if (!$remote->save($dest)) {
            $this->error .= "Impossible to download and save {$name}: ".$remote->error();
        } else {
            $this->success .= "<comment>{$name}</comment> downloaded and saved successfully.";
        }
    }

    /**
     * Copy dropin file from given source path and save it in wp-content folder.
     *
     * @param string $source
     * @param string $dest
     */
    private function copy($source, $dest)
    {
        $sourceBase = basename($source);
        $name = basename($dest);
        try {
            copy($source, $dest)
                ? $this->success .= "<comment>{$name}</comment> copied successfully."
                : $this->error .= "Impossible to copy {$sourceBase} to {$name}.";
        } catch (Exception $e) {
            $this->error .= "Impossible to copy {$sourceBase} to {$name}.";
        }
    }

    /**
     * Check if a string is a valid relative path or an url.
     * Return false if none of them.
     *
     * @param string       $url
     * @param \ArrayAccess $paths
     *
     * @return array
     */
    private function action($url, ArrayAccess $paths)
    {
        $realpath = realpath($paths['root']."/{$url}");
        if ($realpath && is_file($realpath)) {
            return array('copy', $realpath);
        } elseif (filter_var($url, FILTER_VALIDATE_URL)) {
            return array('download', $url);
        }

        return array(false, false);
    }
}
