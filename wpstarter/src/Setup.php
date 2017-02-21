<?php declare( strict_types = 1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter;

use Composer\Script\Event;
use Composer\Composer;
use ArrayObject;
use WCM\WPStarter\Setup\Stepper;
use WCM\WPStarter\Setup\StepperInterface;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\FileBuilder;
use WCM\WPStarter\Setup\OverwriteHelper;
use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\Steps\CheckPathStep;
use WCM\WPStarter\Setup\Steps\DropinsStep;
use WCM\WPStarter\Setup\Steps\EnvExampleStep;
use WCM\WPStarter\Setup\Steps\GitignoreStep;
use WCM\WPStarter\Setup\Steps\IndexStep;
use WCM\WPStarter\Setup\Steps\MoveContentStep;
use WCM\WPStarter\Setup\Steps\WPCliStep;
use WCM\WPStarter\Setup\Steps\WPConfigStep;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class Setup
{
    const WP_PACKAGE = 'johnpbloch/wordpress';

    private static $isRoot = false;

    /**
     * Method that should be used as "post-install-cmd" Composer script.
     *
     * @param \Composer\Script\Event $event
     *
     * @see https://getcomposer.org/doc/articles/scripts.md
     */
    public static function run(Event $event)
    {
        $instance = new static();
        /** @var \Composer\Composer $composer */
        $composer = $event->getComposer();
        $wpVersion = $instance->discoverWpVersion($composer);
        /** @var array $extra */
        $extra = $composer->getPackage()->getExtra();
        $wpStarterConfig = isset($extra['wpstarter']) && is_array($extra['wpstarter'])
            ? $extra['wpstarter']
            : array();
        $config = new Config(
            array_merge(
                $wpStarterConfig,
                array('is-root' => self::$isRoot, 'wp-version' => $wpVersion)
            )
        );
        $io = new IO($event->getIO(), $config['verbosity']);

        $instance->install($composer, $config, $io, $extra);
    }

    /**
     * Method that should be used as "post-install-cmd" Composer script
     * when WP Starter is used as root package.
     *
     * @param \Composer\Script\Event $event
     *
     * @see https://getcomposer.org/doc/articles/scripts.md
     */
    public static function runAsRoot(Event $event)
    {
        self::$isRoot = true;
        self::run($event);
    }

    /**
     * Run WP Starter installation adding all the steps to Builder and launching steps processing.
     *
     * @param \Composer\Composer          $composer
     * @param \WCM\WPStarter\Setup\Config $config
     * @param \WCM\WPStarter\Setup\IO     $io
     * @param array                       $extra
     */
    private function install(Composer $composer, Config $config, IO $io, array $extra)
    {
        $paths = $this->paths($composer, $extra);
        $overwrite = new OverwriteHelper($config, $io, $paths);
        $stepper = new Stepper($io, $overwrite);
        if ($this->stepperAllowed($stepper, $config, $paths, $io)) {
            $builder = new FileBuilder(self::$isRoot);
            $stepper
                ->addStep(new CheckPathStep())
                ->addStep(new WPConfigStep($io, $builder))
                ->addStep(new IndexStep($builder))
                ->addStep(new EnvExampleStep($io, $builder))
                ->addStep(new DropinsStep($io, $overwrite))
                ->addStep(new GitignoreStep($io, $builder))
                ->addStep(new WPCliStep($builder))
                ->addStep(new MoveContentStep($io))
                ->run($paths);
        }
    }

    /**
     * @param \WCM\WPStarter\Setup\StepperInterface $stepper
     * @param \WCM\WPStarter\Setup\Config           $config
     * @param \ArrayObject                          $paths
     * @param \WCM\WPStarter\Setup\IO               $io
     *
     * @return bool
     */
    private function stepperAllowed(
        StepperInterface $stepper,
        Config $config,
        ArrayObject $paths,
        IO $io
    ) {
        if (!$stepper->allowed($config, $paths)) {
            $lines = array(
                'WP Starter installation CANCELED.',
                'wp-config.php was found in root folder and your overwrite settings',
                'do not allow to proceed.',
            );
            $io->block($lines, 'yellow');

            return false;
        }

        return true;
    }

    /**
     * Build paths object.
     *
     * According to johnpbloch/wordpress-core-installer package, WP subdir is 'wordpress' by
     * default, but can can be customized via 'extra.wordpress-content-dir' setting.
     * 'extra.wordpress-content-dir', instead, can be used to customized wp-content folder, with
     * proper automatic url generation for content.
     *
     * @param \Composer\Composer $composer
     * @param array              $extra
     *
     * @return \ArrayObject
     */
    private function paths(Composer $composer, array $extra)
    {
        $cwd = getcwd();
        $wpInstallDir = isset($extra['wordpress-install-dir'])
            ? $extra['wordpress-install-dir']
            : 'wordpress';
        $wpFullDir = "{$cwd}/{$wpInstallDir}";
        $wpSubdir = $this->subdir($cwd, $wpFullDir);
        $wpContent = isset($extra['wordpress-content-dir'])
            ? $this->subdir($cwd, $cwd.'/'.$extra['wordpress-content-dir'])
            : 'wp-content';

        return new ArrayObject($this->normalisePaths(array(
            'root' => $cwd,
            'vendor' => $this->subdir($cwd, $composer->getConfig()->get('vendor-dir')),
            'wp' => $wpSubdir,
            'wp-parent' => $this->subdir($cwd, dirname($wpFullDir)),
            'wp-content' => $wpContent,
            'starter' => $this->subdir($cwd, dirname(__DIR__)),
        )), ArrayObject::STD_PROP_LIST);
    }

    /**
     * Go through installed packages to find WordPress version.
     *
     * @param \Composer\Composer $composer
     *
     * @return string|bool
     */
    private function discoverWpVersion(Composer $composer)
    {
        /** @var array $packages */
        $packages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $version = false;
        while ($version === false && !empty($packages)) {
            $package = array_pop($packages);
            $version = $package->getName() === self::WP_PACKAGE ? $package->getVersion() : false;
        }

        return $version ? implode('.', array_slice(explode('.', $version), 0, 3)) : '';
    }

    /**
     * Just ensures paths use same separator, to allow search/replace.
     *
     * @param array $paths
     *
     * @return array
     */
    private function normalisePaths(array $paths)
    {
        array_walk($paths, function (&$path) {
            is_string($path) and $path = str_replace('\\', '/', $path);
        });

        return $paths;
    }

    /**
     * Strips a parent folder from child.
     *
     * @param string $root
     * @param string $path
     *
     * @return string string
     */
    private function subdir($root, $path)
    {
        $paths = $this->normalisePaths(array($root, $path));

        return trim(preg_replace('|^'.preg_quote($paths[0]).'|', '', $paths[1]), '\\/');
    }
}
