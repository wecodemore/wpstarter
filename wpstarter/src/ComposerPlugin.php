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
use WCM\WPStarter\Setup\Steps;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class ComposerPlugin implements PluginInterface, EventSubscriberInterface, CommandProvider
{

    const WP_PACKAGE_TYPE = 'wordpress-core';
    const EXTRA_KEY = 'wpstarter';

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
        if (!$this->checkWpVersion($wpVersion)) {
            return;
        }

        $extra = (array)$composer->getPackage()->getExtra();

        if (!isset($extra[self::EXTRA_KEY])) {
            return;
        }

        $configs = $extra[self::EXTRA_KEY];
        $dir = getcwd() . DIRECTORY_SEPARATOR;
        if (is_string($configs) && is_file($dir . $configs) && is_readable($dir . $configs)) {
            $content = @file_get_contents($dir . $configs);
            $configs = $content ? @json_decode($content) : [];
        } elseif (is_array($extra[self::EXTRA_KEY])) {
            $configs = $extra[self::EXTRA_KEY];
        }

        $configs[Config::WP_VERSION] = $wpVersion;
        $this->config = new Config($configs, $composer->getConfig());
    }

    /**
     * @inheritdoc
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function getCommands()
    {
        return [new Console\WpStarterCommand()];
    }

    /**
     * Run WP Starter installation adding all the steps to Builder and launching steps processing.
     *
     * It is possible to provide the names of steps to run
     *
     * @param array $steps
     * @return void
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function run(array $steps = [])
    {
        if (!$this->config instanceof Config) {
            $this->io->writeError([
                'Error running WP Starter command.',
                'WordPress not found or found in a too old version.',
            ]);

            return;
        }

        $this->config[Config::CUSTOM_STEPS] or $this->config[Config::CUSTOM_STEPS] = [];
        $this->config[Config::SCRIPS] or $this->config[Config::SCRIPS] = [];

        if ($this->config[Config::VERBOSITY] === null) {
            $verbosity = ($this->io->isDebug() || $this->io->isVeryVerbose()) ? 2 : 1;
            $this->config->appendConfig(Config::VERBOSITY, $verbosity);
        }

        $io = new IO($this->io, $this->config[Config::VERBOSITY]);
        $paths = new Paths($this->composer);
        $overwrite = new OverwriteHelper($this->config, $io, $paths);
        $stepper = new Stepper($io, $overwrite);

        if (!$stepper->allowed($this->config, $paths)) {
            $io->block([
                'WP Starter installation CANCELED.',
                'wp-config.php was found in root folder and your overwrite settings',
                'do not allow to proceed.',
            ], 'yellow');

            return;
        }

        $steps = array_filter($steps, 'is_string');

        $classes = array_merge([
            Steps\CheckPathStep::NAME   => Steps\CheckPathStep::class,
            Steps\WPConfigStep::NAME    => Steps\WPConfigStep::class,
            Steps\IndexStep::NAME       => Steps\IndexStep::class,
            Steps\EnvExampleStep::NAME  => Steps\EnvExampleStep::class,
            Steps\DropinsStep::NAME     => Steps\DropinsStep::class,
            Steps\GitignoreStep::NAME   => Steps\GitignoreStep::class,
            Steps\MoveContentStep::NAME => Steps\MoveContentStep::class,
            Steps\ContentDevStep::NAME  => Steps\ContentDevStep::class,
        ], $this->config[Config::CUSTOM_STEPS]);

        $filesystem = new Filesystem();
        $fileBuilder = new FileBuilder();

        array_walk($classes, function ($stepClass, $name, \stdClass $info) use ($stepper, $steps) {
            if ($name && (!$steps || in_array($name, $steps, true))) {
                $stepper->addStep($this->factoryStep($stepClass, $info));
            }

        }, (object)compact('fileBuilder', 'filesystem', 'io'));

        $stepper->run($paths);
    }

    /**
     * Go through installed packages to find WordPress version.
     * Normalize to always be in the form x.x.x
     *
     * @param  \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     * @return string
     */
    private function discoverWpVersion(Composer $composer, IOInterface $io)
    {
        /** @var array $packages */
        $packages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $vers = [];
        while (!empty($packages) && count($vers) < 2) {
            /** @var \Composer\Package\PackageInterface $package */
            $package = array_pop($packages);
            $package->getType() === self::WP_PACKAGE_TYPE and $vers[] = $package->getVersion();
        }

        if (!$vers) {
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

        return $this->normalizeVersion($vers[0]);
    }

    /**
     * @param string $version
     * @return bool
     */
    private function checkWpVersion($version)
    {
        if (!is_string($version) || !$version || $version === '0.0.0') {
            return false;
        }

        return version_compare($version, '4') >= 0;
    }

    /**
     * @param string $version
     * @return string
     */
    private function normalizeVersion($version)
    {
        $matched = preg_match('~^[0-9]{1,2}(?:[0-9\.]+)?+~', $version, $matches);

        if (!$matched) {
            return '0.0.0';
        }

        $numbers = explode('.', trim($matches[0], '.'));

        return implode('.', array_replace(['0', '0', '0'], array_slice($numbers, 0, 3)));
    }

    /**
     * @param $stepClass
     * @param \stdClass $factoryData
     * @return null|Steps\StepInterface
     */
    private function factoryStep($stepClass, \stdClass $factoryData)
    {
        if (
            !is_string($stepClass)
            || !is_subclass_of($stepClass, Steps\StepInterface::class, true)
        ) {
            return new Steps\NullStep();
        }

        $step = null;

        if (method_exists($stepClass, 'instance')) {
            /** @var callable $factory */
            $factory = [$stepClass, 'instance'];
            $step = $factory($factoryData->io, $factoryData->filesystem, $factoryData->builder);
        }

        $step or $step = is_subclass_of($stepClass, Steps\FileCreationStepInterface::class, true)
            ? new $stepClass($factoryData->io, $factoryData->filesystem, $factoryData->builder)
            : new $stepClass($factoryData->io);

        return $step instanceof Steps\StepInterface ? $step : null;
    }
}
