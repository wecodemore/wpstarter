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

use Composer\Util\Filesystem as FilesystemUtil;
use WCM\WPStarter\Setup\FileBuilder;
use WCM\WPStarter\Setup\Filesystem;
use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\Config;

/**
 * Step that moves wp-content contents from WP package folder to project wp-content folder.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class MoveContentStep implements OptionalStepInterface, FileStepInterface
{
    /**
     * @var \Composer\Util\Filesystem
     */
    private $filesystemUtil;

    /**
     * @var \WCM\WPStarter\Setup\IO
     */
    private $io;

    /**
     * @var \WCM\WPStarter\Setup\Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var \ArrayAccess
     */
    private $paths;

    /**
     * @param \WCM\WPStarter\Setup\IO          $io
     * @param \WCM\WPStarter\Setup\Filesystem  $filesystem
     * @param \WCM\WPStarter\Setup\FileBuilder $filebuilder
     * @return static
     */
    public static function instance(
        IO $io,
        Filesystem $filesystem,
        FileBuilder $filebuilder
    ) {
        return new static($io, $filesystem);
    }

    /**
     * @param \WCM\WPStarter\Setup\IO         $io
     * @param \WCM\WPStarter\Setup\Filesystem $filesystem
     */
    public function __construct(IO $io, Filesystem $filesystem)
    {
        $this->io = $io;
        $this->filesystem = $filesystem;
        $this->filesystemUtil = new FilesystemUtil();
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return 'move-content';
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, \ArrayAccess $paths)
    {
        $this->paths = $paths;

        return $config['move-content'] !== false && ! empty($paths['wp-content']);
    }

    /**
     * @inheritdoc
     */
    public function askConfirm(Config $config, IO $io)
    {
        if ($config['move-content'] !== 'ask') {
            return true;
        }

        $to = $this->filesystemUtil->normalizePath($this->paths['wp-content']);
        $full = $this->filesystemUtil->normalizePath("{$this->paths['root']}/{$to}");
        $lines = [
            'Do you want to move default plugins and themes from',
            'WordPress package wp-content dir to content folder:',
            '"'.$full.'"',
        ];

        return $io->confirm($lines, true);
    }

    /**
     * @inheritdoc
     */
    public function run(\ArrayAccess $paths)
    {
        $from = $this->filesystemUtil->normalizePath("{$paths['root']}/{$paths['wp']}/wp-content");
        $to = $this->filesystemUtil->normalizePath("{$paths['root']}/wp-content");
        if ($from === $to) {
            return self::NONE;
        }

        if (! $this->filesystem->createDir($to)) {
            $this->error = "The folder {$to} does not exists and was not possible to create it.";
        }

        return $this->filesystem->moveDir($from, $to) ? self::SUCCESS : self::ERROR;
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
    public function skipped()
    {
        return '  - wp-content folder contents moving skipped.';
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        return '<comment>wp-content</comment> folder contents moved successfully.';
    }
}
