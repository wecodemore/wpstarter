<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Utils;

use Composer\Composer;
use Composer\Util\Filesystem;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
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

        $this->relative(self::WP_STARTER, 'templates');
    }

    /**
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    private function parse()
    {
        $extra = $this->composer->getPackage()->getExtra();

        $cwd = realpath(getcwd());

        $wpInstallDir = empty($extra['wordpress-install-dir'])
            ? 'wordpress'
            : $extra['wordpress-install-dir'];

        $wpFullDir = realpath("{$cwd}/{$wpInstallDir}");

        $wpContent = empty($extra['wordpress-content-dir'])
            ? $this->filesystem->normalizePath($cwd . '/wp-content')
            : $this->filesystem->normalizePath($cwd . "/{$extra['wordpress-content-dir']}");

        $vendorDir = realpath($this->composer->getConfig()->get('vendor-dir'));

        $binDir = $this->composer->getConfig()->get('bin-dir');

        $wpStarterDir = realpath(dirname(dirname(__DIR__)));

        $paths = [
            self::ROOT       => $this->filesystem->normalizePath($cwd),
            self::VENDOR     => $this->filesystem->normalizePath($vendorDir),
            self::BIN        => $this->filesystem->normalizePath($binDir),
            self::WP         => $this->filesystem->normalizePath($wpFullDir),
            self::WP_PARENT  => $this->filesystem->normalizePath(dirname($wpFullDir)),
            self::WP_CONTENT => $this->filesystem->normalizePath($wpContent),
            self::WP_STARTER => $this->filesystem->normalizePath($wpStarterDir),
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

        return $this->paths[$path] . $to;
    }

    /**
     * @param string $path
     * @param string $to
     * @param bool $isFile
     * @return string
     */
    public function relative($path, $to = '', $isFile = false)
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
            return $to ?: './';
        }

        $subdir = $this->filesystem->findShortestPath(
            $this->paths[self::ROOT],
            $this->paths[$path],
            ! $isFile
        );

        $to and $subdir = rtrim($subdir, '/\\') . $to;

        return $subdir;
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function root($to = '')
    {
        return $this->absolute(self::ROOT, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function vendor($to = '')
    {
        return $this->absolute(self::VENDOR, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function bin($to = '')
    {
        return $this->absolute(self::BIN, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function wp($to = '')
    {
        return $this->absolute(self::WP, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function wp_parent($to = '')
    {
        return $this->absolute(self::WP_PARENT, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function wp_content($to = '')
    {
        return $this->absolute(self::WP_CONTENT, $to);
    }

    /**
     * @param string $to
     * @return string
     * @throws \InvalidArgumentException
     */
    public function wp_starter($to = '')
    {
        return $this->absolute(self::WP_STARTER, $to);
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
}
