<?php declare( strict_types = 1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup\Steps;

use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\Config;
use ArrayAccess;
use Exception;

/**
 * Step that moves wp-content contents from WP package folder to project wp-content folder.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class MoveContentStep implements OptionalStepInterface
{
    /**
     * @var \WCM\WPStarter\Setup\IO
     */
    private $io;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var \ArrayAccess
     */
    private $paths;

    /**
     * @param \WCM\WPStarter\Setup\IO $io
     */
    public function __construct(IO $io)
    {
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    public function allowed(Config $config, ArrayAccess $paths)
    {
        $this->paths = $paths;

        return $config['move-content'] !== false && !empty($paths['wp-content']);
    }

    /**
     * {@inheritdoc}
     */
    public function question(Config $config, IO $io)
    {
        if ($config['move-content'] !== 'ask') {
            return true;
        }
        $to = str_replace('\\', '/', $this->paths['wp-content']);
        $full = str_replace('\\', '/', $this->paths['root']).'/'.ltrim($to, '/');
        $lines = array(
            'Do you want to move default plugins and themes from',
            'WordPress package wp-content dir to content folder:',
            '"'.$full.'"',
        );

        return $io->ask($lines, true);
    }

    /**
     * {@inheritdoc}
     */
    public function run(ArrayAccess $paths)
    {
        $from = str_replace('\\', '/', $paths['root'].'/'.$paths['wp'].'/wp-content');
        $to = str_replace('\\', '/', $paths['root'].'/'.$paths['wp-content']);
        if ($from === $to) {
            return self::NONE;
        }
        if (!is_dir($to) && !mkdir($to, 0755)) {
            $this->error = "The folder {$to} does not exists and was not possible to create it.";
        }

        $this->moveItems(glob($from.'/*'), $from, $to);

        return empty($this->error) ? self::SUCCESS : self::ERROR;
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
    public function skipped()
    {
        return '  - wp-content folder contents moving skipped.';
    }

    /**
     * {@inheritdoc}
     */
    public function success()
    {
        return '<comment>wp-content</comment> folder contents moved successfully.';
    }

    /**
     * Move an array of items from a source to a destination folder, only if not already there.
     * If item is a folder and it's already present, restart recursively, but only once.
     * Because the top level source folder is the original 'wp-content', it means we attempt to move
     * singular theme/plugin folders, but not theme/plugin subfolders.
     *
     * @param array  $items
     * @param string $from
     * @param string $to
     * @param int    $deep
     *
     * @return bool true on success, false on failure
     */
    private function moveItems(array $items, $from, $to, $deep = 0)
    {
        $ok = true;
        while (!empty($items) && !empty($ok)) {
            $item = array_shift($items);
            $ok = $this->moveItem($item, $from, $to, $deep);
            if ($ok && count(scandir(dirname($item))) === 2) {
                // after the move, old containing directory is empty, we can delete it
                @rmdir(dirname($item));
            }
        }

        return $ok;
    }

    /**
     * Move an item from a source to a destination folder, only if not already present there.
     * If item is a folder and it's already present, restart recursively trying to move items in
     * it, but only one level deep.
     *
     * @param string $path relative path of the item to move
     * @param string $from absolute path of the current containing folder
     * @param string $to   absolute path of the target containing folder
     * @param int    $deep
     *
     * @return bool true on success, false on failure
     */
    private function moveItem($path, $from, $to, $deep = 0)
    {
        $dest = str_replace($from, $to, $path);
        try {
            if (!is_dir($dest) && !is_file($dest) && !rename($path, $dest)) {
                $this->error .= "Error on moving {$path} to {$dest}".PHP_EOL;
            } elseif (is_dir($dest) && $deep < 1) {
                return $this->moveItems(glob($path.'/*'), $from, $to, $deep + 1);
            }
        } catch (Exception $e) {
            $this->error .= "Error on moving {$path} to {$dest}".PHP_EOL;
        }

        return true;
    }
}
