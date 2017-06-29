<?php
namespace Gmazzap;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;

/**
 * Composer plugin to change packages installation directory arbitrarly.
 *
 * Useful when more packages we want to change install pathe have no custom type,
 * not depends on installers, and we want to avoid to write an installerr for each of them.
 *
 * How to use:
 *
 * 1) Require the plugin in your root composer.json
 * 2) Add packages to change in your root composer.json in
 *    a `config.extra.packages-custom-paths` object, example:
 *
 *      <code>
 *      {
 *          "config": {
 *              "extra": {
 *                 "packages-custom-paths": {
 *                      "some-vendor/some-package": "/some/target/path"
 *                 }
 *              }
 *          }
 *      }
 *      </code>
 *
 * Target paths must be relative to composer.json dir.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
final class PackageMover implements PluginInterface, EventSubscriberInterface
{

    const EXTRA_KEY = 'packages-custom-paths';

    /**
     * @var array
     */
    private $config;

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'pre-install-cmd' => 'run',
            'pre-update-cmd'  => 'run',
        ];
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $extra = (array)$composer->getPackage()->getExtra();
        $configs = empty($extra[self::EXTRA_KEY]) ? [] : $extra[self::EXTRA_KEY];
        $this->config = (array)$configs;
    }

    /**
     * @param Event $event
     */
    public function run(Event $event)
    {
        $io = $event->getIO();

        if (!$this->config) {
            $io->write(
                [
                    sprintf('%s: no custom paths found, skipping...', __CLASS__),
                    sprintf('Use "extra.%s" to configure custom packages paths.', self::EXTRA_KEY),
                ]
            );

            return;
        }

        $composer = $event->getComposer();

        /** @var Package[] $packages */
        $packages = $composer
            ->getRepositoryManager()
            ->getLocalRepository()
            ->getPackages();

        $manager = $composer->getInstallationManager();
        $fs = new Filesystem();

        foreach ($packages as $package) {
            $packageName = $package->getName();
            if (!$package instanceof Package || !array_key_exists($packageName, $this->config)) {
                continue;
            }

            $package->setTargetDir(filter_var($this->config[$packageName], FILTER_SANITIZE_URL));

            // If original directory exists, delete it
            $origDir = $manager->getInstallPath($package);
            if (is_dir($origDir)) {
                $fs->removeDirectory($origDir);
            }
        }

        $io->writeError(sprintf('%s: All packages target paths changed.', __CLASS__));
    }
}