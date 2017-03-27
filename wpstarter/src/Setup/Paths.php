<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup;

use Composer\Composer;
use Composer\Util\Filesystem;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class Paths implements \ArrayAccess
{

    const ROOT = 'root';
    const VENDOR = 'vendor';
    const BIN = 'bin';
    const WP = 'wp';
    const WP_PARENT = 'wp-parent';
    const WP_CONTENT = 'wp-content';
    const WP_STARTER = 'starter';

    /**
     * @var \SplObjectStorage
     */
    private static $parsed;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $paths = [];

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @param \Composer\Composer $composer
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __construct(Composer $composer)
    {
        self::$parsed === null and self::$parsed = new \SplObjectStorage();
        $this->composer = $composer;
        $this->filesystem = new Filesystem();
        $this->paths = self::$parsed->contains($this->composer)
            ? self::$parsed->offsetGet($this->composer)
            : $this->parse();
    }

    /**
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    private function parse()
    {
        $extra = $this->composer->getPackage()->getExtra();

        $cwd = getcwd();

        $wpInstallDir = empty($extra['wordpress-install-dir'])
            ? 'wordpress'
            : $extra['wordpress-install-dir'];

        $wpFullDir = $this->filesystem->normalizePath("{$cwd}/{$wpInstallDir}");
        $wpContent = empty($extra['wordpress-content-dir'])
            ? 'wp-content'
            : $this->subdir($cwd, "{$cwd}/{$extra['wordpress-content-dir']}");

        $vendor_dir = $this->composer->getConfig()->get('vendor-dir');
        $bin_dir = $this->composer->getConfig()->get('bin-dir');

        $paths = [
            self::ROOT       => $this->filesystem->normalizePath($cwd),
            self::VENDOR     => $this->subdir($cwd, $vendor_dir),
            self::BIN        => $this->subdir($cwd, $bin_dir),
            self::WP         => $wpInstallDir,
            self::WP_PARENT  => $this->subdir($cwd, dirname($wpFullDir)),
            self::WP_CONTENT => $wpContent,
            self::WP_STARTER => $this->subdir($cwd, dirname(__DIR__)),
        ];

        self::$parsed->attach($this->composer, $paths);

        return $paths;
    }

    /**
     * @param string $path
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function absolute($path, $to = '')
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException(
                sprintf('%s requires a string, %s given.', __METHOD__, gettype($path))
            );
        }

        if (!array_key_exists($path, $this->paths)) {
            throw new \InvalidArgumentException(
                sprintf('%s is not a valid WP Starter path key.', $path)
            );
        }

        $to and $to = '/' . ltrim($this->filesystem->normalizePath($to), '/');

        if ($path === self::ROOT) {
            return $this->root() . $to;
        }

        $root = rtrim($this->paths[self::ROOT], '\//') . '/';

        return $this->filesystem->normalizePath($root . $this->paths[$path]) . $to;
    }

    /**
     * @param string $path
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function relative($path, $to = '')
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException(
                sprintf('%s requires a string, %s given.', __METHOD__, gettype($path))
            );
        }

        if (!array_key_exists($path, $this->paths)) {
            throw new \InvalidArgumentException(
                sprintf('%s is not a valid WP Starter path key.', $path)
            );
        }

        $to and $to = '/' . ltrim($this->filesystem->normalizePath($to), '/');

        return $this->paths[self::ROOT] . $to;
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function root($to = '')
    {
        return $this->relative(self::ROOT, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function vendor($to = '')
    {
        return $this->relative(self::VENDOR, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function bin($to = '')
    {
        return $this->relative(self::BIN, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function wp($to = '')
    {
        return $this->relative(self::WP, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function wp_parent($to = '')
    {
        return $this->relative(self::WP_PARENT, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function wp_content($to = '')
    {
        return $this->relative(self::WP_CONTENT, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function wp_starter($to = '')
    {
        return $this->relative(self::WP_STARTER, $to);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->paths);
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \InvalidArgumentException(
                sprintf('%s is not a valid WP Starter path index.', $offset)
            );
        }

        return $this->paths[$offset];
    }

    /**
     * @inheritdoc
     * @throws \BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        if ($this->offsetExists($offset)) {
            throw new \BadMethodCallException(
                sprintf(
                    '%s is append-only: can\'t set %s path because that name is already set.',
                    __CLASS__,
                    $offset
                )
            );
        }

        $this->paths[$offset] = $value;
        self::$parsed->attach($this->composer, $this->paths);
    }

    /**
     * @inheritdoc
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException(sprintf('%s class does not support unset.', __CLASS__));
    }

    /**
     * Strips a parent folder from child.
     *
     * @param  string $root
     * @param  string $path
     * @return string string
     * @throws \InvalidArgumentException
     */
    private function subdir($root, $path)
    {
        $subdir = $this->filesystem->findShortestPath($root, $path, true);
        strpos($subdir, './') === 0 and $subdir = substr($subdir, 2);

        return $subdir;
    }
}
