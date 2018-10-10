<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Step\OptionalStep;

class OverwriteHelper
{
    /**
     * @var bool|string|array
     */
    private $preventFor;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var string
     */
    private $root;

    /**
     * @var \Composer\Util\Filesystem
     */
    private $filesystem;

    /**
     * @param Config $config
     * @param Io $io
     * @param Paths $paths
     * @param \Composer\Util\Filesystem $filesystem
     */
    public function __construct(
        Config $config,
        Io $io,
        Paths $paths,
        \Composer\Util\Filesystem $filesystem
    ) {

        $this->preventFor = $config['prevent-overwrite']->unwrapOrFallback(false);
        if (is_array($this->preventFor)) {
            $trim = function (string $path): string {
                return trim($path, '/');
            };
            $this->preventFor = array_map($trim, $this->preventFor);
        }
        $this->io = $io;
        $this->root = $paths->root();
        $this->filesystem = $filesystem;
    }

    /**
     * Return true if a file does not exist or exists but should be overwritten according to config.
     * Ask user if necessary.
     *
     * @param  string $file
     * @return bool
     */
    public function shouldOverwite(string $file): bool
    {
        if (!is_file($file)) {
            return true;
        }

        if ($this->preventFor === OptionalStep::ASK) {
            $name = basename($file);
            $lines = ["{$name} found in target folder", 'Do you want to overwrite it?'];

            return $this->io->askConfirm($lines, true);
        }

        if (is_array($this->preventFor)) {
            $path = $this->filesystem->normalizePath($file);
            preg_match('#^' . $this->root . '/(.+)#', $path, $matches);
            if (empty($matches[1])) {
                return false;
            }

            $relative = trim($matches[1], '/');

            return !in_array($relative, $this->preventFor, true) || $this->patternCheck($relative);
        }

        return !$this->preventFor;
    }

    /**
     * Check if a file is set to not be overwritten using shell patterns.
     *
     * @param  string $pattern
     * @return bool
     */
    private function patternCheck(string $pattern): bool
    {
        $overwrite = true;
        $config = $this->preventFor;
        while ($overwrite === true && !empty($config)) {
            $overwrite = !fnmatch(array_shift($config), $pattern, FNM_NOESCAPE);
        }

        return $overwrite;
    }
}
