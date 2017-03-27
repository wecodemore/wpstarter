<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup\Steps;

use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\OverwriteHelper;
use WCM\WPStarter\Setup\Paths;
use WCM\WPStarter\Setup\UrlDownloader;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class DropinStep implements FileCreationStepInterface
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
     * @param string $name
     * @param string $url
     * @param \WCM\WPStarter\Setup\IO $io
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
     * @inheritdoc
     */
    public function name()
    {
        return 'dropin-' . pathinfo($this->name, PATHINFO_FILENAME);
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function allowed(Config $config, Paths $paths)
    {
        $this->actionSource = $this->action($this->url, $paths);

        if (empty($this->actionSource[0])) {
            $this->io->error("{$this->url} is not a valid url nor a valid path.");

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function run(Paths $paths)
    {
        $dest = $this->targetPath($paths);
        if (!$this->overwrite->should($dest)) {
            $this->io->comment("  - {$this->name} skipped.");

            return;
        }

        $this->actionSource[0] === 'download'
            ? $this->download($this->actionSource[1], $dest)
            : $this->copy($this->actionSource[1], $dest);
    }

    /**
     * @inheritdoc
     */
    public function error()
    {
        return trim($this->error);
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        return trim($this->success);
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function targetPath(Paths $paths)
    {
        return $paths->absolute(Paths::WP_CONTENT, $this->name);
    }

    /**
     * Download dropin file from given url and save it to in wp-content folder.
     *
     * @param string $url
     * @param string $dest
     * @throws \RuntimeException
     */
    private function download($url, $dest)
    {
        $remote = new UrlDownloader($url, $this->io);
        $name = basename($dest);
        if (!$remote->save($dest)) {
            $this->error .= "Impossible to download and save {$name}: " . $remote->error();
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
        } catch (\Exception $e) {
            $this->error .= "Impossible to copy {$sourceBase} to {$name}.";
        }
    }

    /**
     * Check if a string is a valid relative path or an url.
     * Return false if none of them.
     *
     * @param  string $url
     * @param  Paths $paths
     * @return array
     * @throws \InvalidArgumentException
     */
    private function action($url, Paths $paths)
    {
        $realpath = realpath($paths->root($url));
        if ($realpath && is_file($realpath)) {
            return ['copy', $realpath];
        } elseif (filter_var($url, FILTER_VALIDATE_URL)) {
            return ['download', $url];
        }

        return [false, false];
    }
}
