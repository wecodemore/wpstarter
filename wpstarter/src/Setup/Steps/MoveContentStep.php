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

use WCM\WPStarter\Setup\FileBuilder;
use WCM\WPStarter\Setup\Filesystem;
use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\OverwriteHelper;

/**
 * Step that moves wp-content contents from WP package folder to project wp-content folder.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class MoveContentStep implements OptionalStepInterface, FileStepInterface
{
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
     * @param \WCM\WPStarter\Setup\IO              $io
     * @param \WCM\WPStarter\Setup\Filesystem      $filesystem
     * @param \WCM\WPStarter\Setup\FileBuilder     $filebuilder
     * @param \WCM\WPStarter\Setup\OverwriteHelper $overwriteHelper
     * @return static
     */
    public static function instance(
        IO $io,
        Filesystem $filesystem,
        FileBuilder $filebuilder,
        OverwriteHelper $overwriteHelper
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
    public function question(Config $config, IO $io)
    {
        if ($config['move-content'] !== 'ask') {
            return true;
        }
        $to = str_replace('\\', '/', $this->paths['wp-content']);
        $full = str_replace('\\', '/', $this->paths['root']).'/'.ltrim($to, '/');
        $lines = [
            'Do you want to move default plugins and themes from',
            'WordPress package wp-content dir to content folder:',
            '"'.$full.'"',
        ];

        return $io->ask($lines, true);
    }

    /**
     * @inheritdoc
     */
    public function run(\ArrayAccess $paths)
    {
        $from = str_replace('\\', '/', $paths['root'].'/'.$paths['wp'].'/wp-content');
        $to = str_replace('\\', '/', $paths['root'].'/'.$paths['wp-content']);
        if ($from === $to) {
            return self::NONE;
        }

        if ($this->filesystem->createDir($to)) {
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
