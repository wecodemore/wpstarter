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
     */
    public function __construct(Composer $composer)
    {
        is_null(self::$parsed) and self::$parsed = new \SplObjectStorage();
        $this->composer = $composer;
        $this->filesystem = new Filesystem();
        $this->paths = self::$parsed->contains($this->composer)
            ? self::$parsed->offsetGet($this->composer)
            : $this->parse();
    }

    /**
     * @return array
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

        $paths = [
            'root'       => $this->filesystem->normalizePath($cwd),
            'vendor'     => $this->subdir($cwd, $this->composer->getConfig()->get('vendor-dir')),
            'wp'         => $wpInstallDir,
            'wp-parent'  => $this->subdir($cwd, dirname($wpFullDir)),
            'wp-content' => $wpContent,
            'starter'    => $this->subdir($cwd, dirname(__DIR__)),
        ];

        self::$parsed->attach($this->composer, $paths);

        return $paths;
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
     */
    public function offsetGet($offset)
    {
        if (! $this->offsetExists($offset)) {
            throw new \InvalidArgumentException(sprintf("%s is not a valid WP Starter path index."));
        }

        return $this->paths[$offset];
    }

    /**
     * @inheritdoc
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
     */
    private function subdir($root, $path)
    {
        $subdir = $this->filesystem->findShortestPath($root, $path, true);
        strpos($subdir, './') === 0 and $subdir = substr($subdir, 2);

        return $subdir;
    }
}
