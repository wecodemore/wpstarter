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

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capability\CommandProvider;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\FileBuilder;
use WCM\WPStarter\Setup\Filesystem;
use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\OverwriteHelper;
use WCM\WPStarter\Setup\Paths;
use WCM\WPStarter\Setup\Stepper;
use WCM\WPStarter\Setup\StepperInterface;
use WCM\WPStarter\Setup\Steps;


/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class ComposerPlugin implements PluginInterface, EventSubscriberInterface, CommandProvider
{
    const WP_PACKAGE_TYPE = 'wordpress-core';
    const WP_MIN_VER      = '4.4.3';

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * @var \WCM\WPStarter\Setup\Config
     */
    private $config;

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'run',
            'post-update-cmd'  => 'run',
        ];
    }

    /**
     * @inheritdoc
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $wpVersion = $this->discoverWpVersion($composer, $io);

        // if no or wrong WP found we do nothing, so install() will show an error not findind config
        if ($this->checkWpVersion($wpVersion)) {
            $extra = (array)$composer->getPackage()->getExtra();
            $configs = isset($extra['wpstarter']) && is_array($extra['wpstarter'])
                ? $extra['wpstarter']
                : [];
            $configs['wp-version'] = $wpVersion;
            $this->config = new Config($configs);
        }
    }

    /**
     * @inheritdoc
     */
    public function getCommands()
    {
        return [new Command\WpStarterCommand()];
    }

    /**
     * Run WP Starter installation adding all the steps to Builder and launching steps processing.
     *
     * It is possible to provide the names of steps to run
     *
     * @param array $steps
     * @return
     */
    public function run(array $steps = [])
    {
        if ($this->config instanceof Config) {
            return $this->io->writeError([
                'Error running WP Starter command.',
                'WordPress not found or found in a too old version.',
                'Minimum required WordPress version is '.self::WP_MIN_VER.'.',
            ]);
        }

        $this->config->offsetExists('custom-steps') or $this->config['custom-steps'] = [];
        $this->config->offsetExists('scripts') or $this->config['scripts'] = [];
        if (! $this->config->offsetExists('verbosity')) {
            $verbosity = ($this->io->isDebug() || $this->io->isVeryVerbose()) ? 2 : 1;
            $this->config->appendConfig('verbosity', $verbosity);
        }

        $io = new IO($this->io, $this->config['verbosity']);
        $paths = new Paths($this->composer);
        $overwrite = new OverwriteHelper($this->config, $io, $paths);
        $stepper = new Stepper($io, $overwrite);

        $steps = array_filter($steps, 'is_string');

        if ($this->stepperAllowed($stepper, $this->config, $paths, $io)) {
            $filesystem = new Filesystem();
            $fileBuilder = new FileBuilder();

            $classes = array_merge([
                'check-paths'         => Steps\CheckPathStep::class,
                'build-wpconfig'      => Steps\WPConfigStep::class,
                'build-index'         => Steps\IndexStep::class,
                'build-env-example'   => Steps\EnvExampleStep::class,
                'dropins'             => Steps\DropinsStep::class,
                'build-gitignore'     => Steps\GitignoreStep::class,
                'move-content'        => Steps\MoveContentStep::class,
                'publish-content-dev' => Steps\ContentDevStep::class,
            ], $this->config['custom-steps']);

            array_walk(
                $classes,
                function ($stepClass) use ($stepper, $fileBuilder, $filesystem, $io, $steps) {
                    $stepObj = $this->factoryStep($stepClass, $io, $filesystem, $fileBuilder);
                    if ($stepObj && (! $steps || in_array($stepObj->name(), $steps, true))) {
                        $stepper->addStep($stepObj);
                    }
                }
            );

            $stepper->run($paths);
        }
    }

    /**
     * Go through installed packages to find WordPress version.
     * Normalize to always be in the form x.x.x
     *
     * @param  \Composer\Composer      $composer
     * @param \Composer\IO\IOInterface $io
     * @return string
     */
    private function discoverWpVersion(Composer $composer, IOInterface $io)
    {
        /** @var array $packages */
        $packages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $vers = [];
        while (! empty($packages) && count($vers) < 2) {
            /** @var \Composer\Package\PackageInterface $package */
            $package = array_pop($packages);
            $package->getType() === self::WP_PACKAGE_TYPE and $vers[] = $package->getVersion();
        }

        if (! $vers) {
            return '0.0.0';
        }

        if (count($vers) > 1) {
            $io->writeError([
                'Seems that more WordPress core packages are provided.',
                'WP Starter only support a single WordPress core package.',
                'WP Starter will NOT work.',
            ]);

            return '0.0.0';
        }

        return implode('.', array_pad(array_map(function ($part) {
            $parts = explode('-', $part, 2);
            $part = $parts[0];

            return is_numeric($part) ? abs((int)$part) : 0;
        }, explode('.', $vers[0], 3)), 3, 0));
    }

    /**
     * @param string $version
     * @return bool
     */
    private function checkWpVersion($version)
    {
        if (! $version || $version === '0.0.0') {
            return false;
        }

        return version_compare($version, self::WP_MIN_VER) >= 0;
    }

    /**
     * Instantiate a step instance using the best method available.
     *
     * @param string                           $stepClass
     * @param \WCM\WPStarter\Setup\IO          $io
     * @param \WCM\WPStarter\Setup\Filesystem  $filesystem
     * @param \WCM\WPStarter\Setup\FileBuilder $builder
     * @return \WCM\WPStarter\Setup\Steps\StepInterface|null
     */
    private function factoryStep(
        $stepClass,
        IO $io,
        Filesystem $filesystem,
        FileBuilder $builder
    ) {
        if (
            ! is_string($stepClass)
            || ! is_subclass_of($stepClass, Steps\StepInterface::class, true)
        ) {
            return;
        }

        $step = null;

        if (method_exists($stepClass, 'instance')) {
            /** @var callable $factory */
            $factory = [$stepClass, 'instance'];
            $step = $factory($io, $filesystem, $builder);
        }

        $step or $step = is_subclass_of($stepClass, Steps\FileCreationStepInterface::class, true)
            ? new $stepClass($io, $filesystem, $builder)
            : new $stepClass($io);

        return $step instanceof StepInterface ? $step : null;
    }

    /**
     * @param \WCM\WPStarter\Setup\StepperInterface $stepper
     * @param \WCM\WPStarter\Setup\Config           $config
     * @param \WCM\WPStarter\Setup\Paths            $paths
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
            $io->block([
                'WP Starter installation CANCELED.',
                'wp-config.php was found in root folder and your overwrite settings',
                'do not allow to proceed.',
            ], 'yellow');

            return false;
        }

        return true;
    }
}
