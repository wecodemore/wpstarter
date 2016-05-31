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
use WCM\WPStarter\Setup\Filesystem;
use WCM\WPStarter\Setup\Stepper;
use WCM\WPStarter\Setup\StepperInterface;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\FileBuilder;
use WCM\WPStarter\Setup\OverwriteHelper;
use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\Steps\StepInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class Setup
{
    const WP_PACKAGE = 'johnpbloch/wordpress';

    /**
     * @var bool
     */
    private static $isRoot = false;

    /**
     * Method that should be used as "post-install-cmd" Composer script.
     *
     * @param \Composer\Script\Event $event
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
            : [];
        $wpStarterConfig['is-root'] = self::$isRoot;
        $wpStarterConfig['wp-version'] = $wpVersion;
        $config = new Config($wpStarterConfig);
        $io = new IO($event->getIO(), $config['verbosity']);

        $instance->install(new Paths($event), $config, $io);
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
     * Run WP Starter installation adding all the steps to Builder and launching steps processing.
     *
     * @param \WCM\WPStarter\Paths        $paths
     * @param \WCM\WPStarter\Setup\Config $config
     * @param \WCM\WPStarter\Setup\IO     $io
     */
    private function install(Paths $paths, Config $config, IO $io)
    {
        $overwrite = new OverwriteHelper($config, $io, $paths);
        $stepper = new Stepper($io, $overwrite);
        if ($this->stepperAllowed($stepper, $config, $paths, $io)) {
            $filesystem = new Filesystem();
            $builder = new FileBuilder(self::$isRoot, $filesystem);

            $classes = array_merge([
                'check-path'   => '\\WCM\\WPStarter\\Setup\\Steps\\CheckPathStep',
                'wp-config'    => '\\WCM\\WPStarter\\Setup\\Steps\\WPConfigStep',
                'index'        => '\\WCM\\WPStarter\\Setup\\Steps\\IndexStep',
                'env-example'  => '\\WCM\\WPStarter\\Setup\\Steps\\EnvExampleStep',
                'dropins'      => '\\WCM\\WPStarter\\Setup\\Steps\\DropinsStep',
                'gitignore'    => '\\WCM\\WPStarter\\Setup\\Steps\\GitignoreStep',
                'move-content' => '\\WCM\\WPStarter\\Setup\\Steps\\MoveContentStep',
            ], $config['custom-steps']);

            array_walk(
                $classes,
                function ($stepClass) use ($stepper, $io, $builder, $filesystem) {
                    $step = $this->factoryStep($stepClass, $filesystem, $builder);
                    $step and $stepper->addStep($step);
                }
            );

            $stepper->run($paths);
        }
    }

    /**
     * Instantiate a step instance using the best method available.
     *
     * @param string                               $stepClass
     * @param \WCM\WPStarter\Setup\IO              $io
     * @param \WCM\WPStarter\Setup\Filesystem      $filesystem
     * @param \WCM\WPStarter\Setup\FileBuilder     $builder
     * @return \WCM\WPStarter\Setup\Steps\StepInterface|null
     */
    private function factoryStep(
        $stepClass,
        IO $io,
        Filesystem $filesystem,
        FileBuilder $builder
    ) {
        $ns = 'WCM\\WPStarter\\Setup\\Steps\\';

        if (! is_subclass_of($stepClass, $ns.'StepInterface', true)) {
            return;
        }

        $step = null;

        if (method_exists($stepClass, 'instance')) {
            /** @var callable $factory */
            $factory = [$stepClass, 'instance'];
            $step = $factory($io, $filesystem, $builder);
        }

        $step or $step = is_subclass_of($stepClass, $ns.'FileStepInterface', true)
            ? new $stepClass($io, $filesystem, $builder)
            : new $stepClass($io);

        return $step instanceof StepInterface ? $step : null;
    }

    /**
     * @param \WCM\WPStarter\Setup\StepperInterface $stepper
     * @param \WCM\WPStarter\Setup\Config           $config
     * @param \WCM\WPStarter\Paths                  $paths
     * @param \WCM\WPStarter\Setup\IO               $io
     * @return bool
     */
    private function stepperAllowed(
        StepperInterface $stepper,
        Config $config,
        Paths $paths,
        IO $io
    ) {
        if (! $stepper->allowed($config, $paths)) {
            $lines = [
                'WP Starter installation CANCELED.',
                'wp-config.php was found in root folder and your overwrite settings',
                'do not allow to proceed.',
            ];
            $io->block($lines, 'yellow');

            return false;
        }

        return true;
    }

    /**
     * Go through installed packages to find WordPress version.
     *
     * @param  \Composer\Composer $composer
     * @return string|bool
     */
    private function discoverWpVersion(Composer $composer)
    {
        /** @var array $packages */
        $packages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $version = false;
        while ($version === false && ! empty($packages)) {
            $package = array_pop($packages);
            $version = $package->getName() === self::WP_PACKAGE ? $package->getVersion() : false;
        }

        return $version ? implode('.', array_slice(explode('.', $version), 0, 3)) : '';
    }
}
