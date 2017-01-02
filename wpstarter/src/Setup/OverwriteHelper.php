<?php declare( strict_types = 1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup;

use ArrayAccess;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class OverwriteHelper
{
    /**
     * @var bool|string|array
     */
    private $config;

    /**
     * @var \WCM\WPStarter\IO
     */
    private $io;

    /**
     * @var string
     */
    private $root;

    /**
     * @param \WCM\WPStarter\Setup\Config $config
     * @param \WCM\WPStarter\Setup\IO     $io
     * @param \ArrayAccess                $paths
     */
    public function __construct(Config $config, IO $io, ArrayAccess $paths)
    {
        $this->config = $config['prevent-overwrite'];
        if (is_array($this->config)) {
            $this->config = array_map(array($this, 'normalise'), $this->config);
        }
        $this->io = $io;
        $this->root = $this->normalise($paths['root']);
    }

    /**
     * Return true if a file does not exist or exists but should be overwritten according to config.
     * Ask user if necessary.
     *
     * @param string $file
     *
     * @return bool
     */
    public function should($file)
    {
        if (!is_file($file)) {
            return true;
        }
        if ($this->config === 'ask') {
            $name = basename($file);
            $lines = array("{$name} found in target folder. Do you want to overwrite it?");

            return $this->io->ask($lines, true);
        }
        if (is_array($this->config)) {
            $relative = trim(str_replace($this->root, '', $this->normalise($file)), '/');

            return in_array($relative, $this->config, true)
                ? false
                : $this->patternCheck($relative);
        }

        return empty($this->config);
    }

    /**
     * Check if a file is set to not be overwritten using shell patterns.
     *
     * @param string $file
     *
     * @return bool
     */
    private function patternCheck($file)
    {
        $overwrite = true;
        $config = $this->config;
        while ($overwrite === true && !empty($config)) {
            $overwrite = fnmatch(array_shift($config), $file, FNM_NOESCAPE) ? false : true;
        }

        return $overwrite;
    }

    /**
     * Normalize path for no issue on str_replace.
     *
     * @param string $path
     *
     * @return string
     */
    private function normalise($path)
    {
        return trim(str_replace('\\', '/', $path), '/');
    }
}
