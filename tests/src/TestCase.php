<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests;

use Composer;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use WeCodeMore\WpStarter\Cli;
use WeCodeMore\WpStarter\Config;
use WeCodeMore\WpStarter\Io;
use WeCodeMore\WpStarter\Util;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;
    use PhpUnitCrossVersion;

    /**
     * @before
     */
    protected function before()
    {
        parent::setUp();
        $this->startMockery();
    }

    /**
     * @after
     */
    protected function after()
    {
        $this->closeMockery();
        parent::tearDown();
    }

    /**
     * @return string
     */
    protected function fixturesPath(): string
    {
        return str_replace('\\', '/', getenv('TESTS_FIXTURES_PATH'));
    }

    /**
     * @return string
     */
    protected function packagePath(): string
    {
        return str_replace('\\', '/', getenv('PACKAGE_PATH'));
    }

    /**
     * @param array $configs
     * @param array $extra
     * @param string $vendorDir
     * @param string $binDir
     * @return Config\Config
     */
    protected function factoryConfig(
        array $configs = [],
        array $extra = [],
        string $vendorDir = __DIR__,
        string $binDir = __DIR__
    ): Config\Config {

        return new Config\Config($configs, $this->factoryValidator($extra, $vendorDir, $binDir));
    }

    /**
     * @param array $extra
     * @param string $vendorDir
     * @param string $binDir
     * @return Config\Validator
     */
    protected function factoryValidator(
        array $extra = [],
        string $vendorDir = __DIR__,
        string $binDir = __DIR__
    ): Config\Validator {

        $config = \Mockery::mock(Composer\Config::class);
        $config->allows('get')->with('vendor-dir')->andReturn($vendorDir);
        $config->allows('get')->with('bin-dir')->andReturn($binDir);
        $composer = \Mockery::mock(Composer\Composer::class);
        $composer->allows('getConfig')->andReturn($config);
        $composer->allows('getPackage->getExtra')->andReturn($extra);

        $filesystem = new Composer\Util\Filesystem();

        return new Config\Validator($this->factoryPaths(), $filesystem);
    }

    /**
     * @param mixed ...$objects
     * @return Util\Locator
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    protected function factoryLocator(...$objects): Util\Locator
    {
        // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
        // phpcs:enable Generic.Metrics.NestingLevel

        $reflection = new \ReflectionClass(Util\Locator::class);
        /** @var Util\Locator $locator */
        $locator = $reflection->newInstanceWithoutConstructor();

        static $supportedObjects;
        $supportedObjects or $supportedObjects = [
            Composer\Composer::class,
            Composer\Config::class,
            Composer\IO\IOInterface::class,
            Composer\Util\Filesystem::class,
            Composer\Util\RemoteFilesystem::class,
            Config\Config::class,
            Util\OverwriteHelper::class,
            Util\Filesystem::class,
            Util\Paths::class,
            Io\Io::class,
            Util\UrlDownloader::class,
            Util\FileContentBuilder::class,
            Util\OverwriteHelper::class,
            Util\Salter::class,
            Cli\PharInstaller::class,
        ];

        $closure = function (...$objects) use ($supportedObjects) {
            $this->objects = [];
            foreach ($objects as $object) {
                foreach ($supportedObjects as $supportedObject) {
                    if (is_a($object, $supportedObject)) {
                        $this->objects[$supportedObject] = $object;
                        break;
                    }
                }
            }
        };

        \Closure::bind($closure, $locator, Util\Locator::class)(...$objects);

        return $locator;
    }

    /**
     * @param array|null $extra
     * @return Util\Paths
     */
    protected function factoryPaths(array $extra = null): Util\Paths
    {
        $root = $this->fixturesPath() . '/paths-root';

        $config = \Mockery::mock(Composer\Config::class);
        $config->allows('get')->with('vendor-dir')->andReturn("{$root}/vendor");
        $config->allows('get')->with('bin-dir')->andReturn("{$root}/vendor/bin");

        is_array($extra) or $extra = [
            'wordpress-install-dir' => 'public/wp',
            'wordpress-content-dir' => 'public/wp-content',
        ];

        return Util\Paths::withRoot($root, $config, $extra, new Composer\Util\Filesystem());
    }

    /**
     * @param Composer\IO\IOInterface|null $io
     * @return Composer\Composer
     */
    protected function factoryComposer(?Composer\IO\IOInterface $io = null): Composer\Composer
    {
        $path = getenv('PACKAGE_PATH') . '/composer.json';

        return Composer\Factory::create($io ?? new TestIo(), $path, true);
    }
}
