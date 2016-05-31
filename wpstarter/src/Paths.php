<?php
/*
 * This file is part of the wpstarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter;

use Composer\Script\Event;


/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package wpstarter
 */
final class Paths implements \ArrayAccess
{
    /**
     * @var \SplObjectStorage
     */
    private static $parsed;

    /**
     * @var array
     */
    private $paths = [];

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @param \Composer\Script\Event $event
     */
    public function __construct(Event $event)
    {
        is_null(self::$parsed) and self::$parsed = new \SplObjectStorage();
        $this->composer = $event->getComposer();
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

        $cwd = $this->normalise([getcwd()]);
        $cwd = reset($cwd);
        $wpInstallDir = isset($extra['wordpress-install-dir'])
            ? $extra['wordpress-install-dir']
            : 'wordpress';
        $wpFullDir = "{$cwd}/{$wpInstallDir}";
        $wpSubdir = $this->subdir($cwd, $wpFullDir);
        $wpContent = isset($extra['wordpress-content-dir'])
            ? $this->subdir($cwd, $cwd.'/'.$extra['wordpress-content-dir'])
            : 'wp-content';

        $paths = [
            'root'       => $cwd,
            'vendor'     => $this->subdir($cwd, $this->composer->getConfig()->get('vendor-dir')),
            'wp'         => $wpSubdir,
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
     * Just ensures paths use same separator, to allow search/replace.
     *
     * @param  array $paths
     * @return array
     */
    private function normalise(array $paths)
    {
        array_walk($paths, function (&$path) {
            is_string($path) and $path = str_replace('\\', '/', $path);
        });

        return $paths;
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
        $paths = $this->normalise([$root, $path]);

        return trim(preg_replace('|^'.preg_quote($paths[0]).'|', '', $paths[1]), '\\/');
    }
}
