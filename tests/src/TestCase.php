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

    /**
     * @before
     */
    protected function before(): void
    {
        parent::setUp();
        $this->startMockery();
    }

    /**
     * @after
     */
    protected function after(): void
    {
        $this->closeMockery();
        parent::tearDown();
    }

    /**
     * @return string
     */
    protected function fixturesPath(): string
    {
        $path = getenv('TESTS_FIXTURES_PATH');
        assert(is_string($path));

        return str_replace('\\', '/', $path);
    }

    /**
     * @return string
     */
    protected function packagePath(): string
    {
        $path = getenv('PACKAGE_PATH');
        assert(is_string($path));

        return str_replace('\\', '/', $path);
    }

    /**
     * @param array<mixed> $configs
     * @param array<string, mixed> $extra
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
     * @param array<string, mixed> $extra
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
        $config->shouldReceive('get')->with('vendor-dir')->andReturn($vendorDir);
        $config->shouldReceive('get')->with('bin-dir')->andReturn($binDir);
        $composer = \Mockery::mock(Composer\Composer::class);
        $composer->shouldReceive('getConfig')->andReturn($config);
        $composer->shouldReceive('getPackage->getExtra')->andReturn($extra);

        $filesystem = new Composer\Util\Filesystem();

        return new Config\Validator($this->factoryPaths(), $filesystem);
    }

    /**
     * @param object ...$objects
     * @return Util\Locator
     *
     * phpcs:disable Inpsyde.CodeQuality.NestingLevel
     */
    protected function factoryLocator(object ...$objects): Util\Locator
    {
        // phpcs:enable Inpsyde.CodeQuality.NestingLevel

        $reflection = new \ReflectionClass(Util\Locator::class);
        /** @var Util\Locator $locator */
        $locator = $reflection->newInstanceWithoutConstructor();

        static $supportedObjects;
        $supportedObjects or $supportedObjects = [
            Composer\Config::class,
            Composer\IO\IOInterface::class,
            Composer\Util\Filesystem::class,
            Composer\Util\RemoteFilesystem::class,
            Config\Config::class,
            Util\Filesystem::class,
            Util\Paths::class,
            Io\Io::class,
            Util\UrlDownloader::class,
            Util\FileContentBuilder::class,
            Util\OverwriteHelper::class,
            Util\Salter::class,
            Cli\PharInstaller::class,
        ];

        $closure = function (object ...$objects) use ($supportedObjects): void {
            $this->objects = []; // @phpstan-ignore property.notFound
            foreach ($objects as $object) {
                /** @var list<class-string> $supportedObjects */
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
     * @param array<string, mixed>|null $extra
     * @return Util\Paths
     */
    protected function factoryPaths(?array $extra = null): Util\Paths
    {
        $root = $this->fixturesPath() . '/paths-root';

        $config = \Mockery::mock(Composer\Config::class);
        $config->shouldReceive('get')->with('vendor-dir')->andReturn("{$root}/vendor");
        $config->shouldReceive('get')->with('bin-dir')->andReturn("{$root}/vendor/bin");

        $extra ??= [
            'wordpress-install-dir' => 'public/wp',
            'wordpress-content-dir' => 'public/wp-content',
        ];

        return Util\Paths::withRoot($root, $config, $extra, new Composer\Util\Filesystem());
    }
}
