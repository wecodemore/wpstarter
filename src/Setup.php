<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter;

use Composer\Script\Event;
use Composer\Composer;
use ArrayObject;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class Setup
{
    const PACKAGE = 'gmazzap/wpstarter';

    private static $isRoot = false;

    private $paths;
    private $builder;

    /**
     * Method that should be used as "post-install-cmd" Composer script.
     *
     * @param \Composer\Script\Event $event
     * @see https://getcomposer.org/doc/articles/scripts.md
     */
    public static function run(Event $event)
    {
        $io = new IO($event->getIO());
        $instance = new static($event->getComposer(), new Builder($io, self::$isRoot));
        $instance->install();
    }

    /**
     * Method that should be used as "post-install-cmd" Composer script
     * when WP Starter is used as root package.
     *
     * @param \Composer\Script\Event $event
     * @see https://getcomposer.org/doc/articles/scripts.md
     */
    public static function runAsRoot(Event $event)
    {
        self::$isRoot = true;
        self::run($event);
    }

    /**
     * Constructor. Set paths and Builder objects.
     *
     * According to johnpbloch/wordpress-core-installer package, WP subdir is 'wordpress' by
     * default, but can can be customized via 'extra.wordpress-content-dir' setting.
     * 'extra.wordpress-content-dir', instead, can be used to customized wp-content folder, with
     * proper automatic url generation for content.
     *
     * @param \Composer\Composer     $composer
     * @param \WCM\WPStarter\Builder $builder
     * @see \johnpbloch\Composer\WordPressCoreInstaller::getInstallPath()
     */
    public function __construct(Composer $composer, Builder $builder)
    {
        $this->builder = $builder;
        /** @var array $extra */
        $extra = $composer->getPackage()->getExtra() ?: array();
        /** @var string $rootPath Package root directory */
        $rootPath = getcwd();
        /** @var string $wpSubdir WordPress subdir */
        $wpSubdir = isset($extra['wordpress-install-dir'])
            ? $extra['wordpress-install-dir']
            : 'wordpress';
        /** @var string $wpContent wp-content folder */
        $wpContent = isset($extra['wordpress-content-dir'])
            ? $extra['wordpress-content-dir']
            : 'wp-content';
        $this->paths = new ArrayObject($this->normalisePaths(array(
            'root'    => $rootPath,
            'vendor'  => $this->subdir($rootPath, $composer->getConfig()->get('vendor-dir')),
            'wp'      => $wpSubdir,
            'content' => $wpContent,
            'starter' => self::$isRoot ? $rootPath : $this->subdir($rootPath, dirname(__DIR__)),
        )), ArrayObject::STD_PROP_LIST);
    }

    /**
     * Uses Builder object to run WP Starter installation routine.
     *
     * @uses \WCM\WPStarter\Builder::build()
     */
    private function install()
    {
        $this->builder->build($this->paths);
    }

    /**
     * Just ensures paths use same separator, to allow search/replace.
     *
     * @param  array $paths
     * @return array
     */
    private function normalisePaths(array $paths)
    {
        array_walk($paths, function (&$path) {
            $path = is_string($path)
                ? str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path)
                : $path;
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
        return trim(preg_replace('|^'.preg_quote(realpath($root)).'|', '', realpath($path)), '\\/');
    }
}
