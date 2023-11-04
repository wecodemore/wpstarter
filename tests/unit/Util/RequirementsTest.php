<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use Composer\IO\NullIO;
use Composer\Package\Package;
use WeCodeMore\WpStarter\ComposerPlugin;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Util\Requirements;

class RequirementsTest extends TestCase
{
    public function testGenericCommandInstanceCreation(): void
    {
        $composer = \Mockery::mock(\Composer\Composer::class);
        $composerConfig = \Mockery::mock(\Composer\Config::class);
        $composerConfig->allows('get')->andReturn('');
        $composer->allows('getPackage->getExtra')->andReturn([]);
        $composer->allows('getConfig')->andReturn($composerConfig);

        $io = new NullIO();
        $filesystem = new \Composer\Util\Filesystem();
        $instance = Requirements::forGenericCommand($composer, $io, $filesystem);
        $config = $instance->config();

        static::assertTrue($config[Config::IS_WPSTARTER_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_WPSTARTER_SELECTED_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_UPDATE]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_INSTALL]->unwrap());
    }

    public function testSelectedStepsCommandInstanceCreation(): void
    {
        $composer = \Mockery::mock(\Composer\Composer::class);
        $composerConfig = \Mockery::mock(\Composer\Config::class);
        $composerConfig->allows('get')->andReturn('');
        $composer->allows('getPackage->getExtra')->andReturn([]);
        $composer->allows('getConfig')->andReturn($composerConfig);

        $io = new NullIO();
        $filesystem = new \Composer\Util\Filesystem();
        $instance = Requirements::forSelectedStepsCommand($composer, $io, $filesystem);
        $config = $instance->config();

        static::assertTrue($config[Config::IS_WPSTARTER_COMMAND]->unwrap());
        static::assertTrue($config[Config::IS_WPSTARTER_SELECTED_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_UPDATE]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_INSTALL]->unwrap());
    }

    public function testComposerInstallInstanceCreation(): void
    {
        $composer = \Mockery::mock(\Composer\Composer::class);
        $composerConfig = \Mockery::mock(\Composer\Config::class);
        $composerConfig->allows('get')->andReturn('');
        $composer->allows('getPackage->getExtra')->andReturn([]);
        $composer->allows('getConfig')->andReturn($composerConfig);

        $io = new NullIO();
        $filesystem = new \Composer\Util\Filesystem();
        $pkg1 = new Package('one', '1.0.0.0', '1.0.0');
        $pkg2 = new Package('two', '2.0.0.0', '2.0');
        $instance = Requirements::forComposerInstall($composer, $io, $filesystem, $pkg1, $pkg2);
        $config = $instance->config();

        static::assertFalse($config[Config::IS_WPSTARTER_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_WPSTARTER_SELECTED_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_UPDATE]->unwrap());
        static::assertTrue($config[Config::IS_COMPOSER_INSTALL]->unwrap());
        static::assertSame([$pkg1, $pkg2], $config[Config::COMPOSER_UPDATED_PACKAGES]->unwrap());
    }

    public function testComposerUpdateInstanceCreation(): void
    {
        $composer = \Mockery::mock(\Composer\Composer::class);
        $composerConfig = \Mockery::mock(\Composer\Config::class);
        $composerConfig->allows('get')->andReturn('');
        $composer->allows('getPackage->getExtra')->andReturn([]);
        $composer->allows('getConfig')->andReturn($composerConfig);

        $io = new NullIO();
        $filesystem = new \Composer\Util\Filesystem();
        $instance = Requirements::forComposerUpdate($composer, $io, $filesystem);
        $config = $instance->config();

        static::assertFalse($config[Config::IS_WPSTARTER_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_WPSTARTER_SELECTED_COMMAND]->unwrap());
        static::assertTrue($config[Config::IS_COMPOSER_UPDATE]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_INSTALL]->unwrap());
        static::assertSame([], $config[Config::COMPOSER_UPDATED_PACKAGES]->unwrap());
    }

    /**
     * When no configs are there, and config file is not there, configuration ends up as default.
     */
    public function testConfigsAreEmptyIfNoExtraValue(): void
    {
        // custom root to make sure wpstarter.json in fixtures root is not loaded

        static::assertSame([], $this->executeExtractConfig([], '/'));
    }

    /**
     * Settings in extra settings are loaded.
     */
    public function testConfigsContainsExtraIfThere(): void
    {
        $extra = [ComposerPlugin::EXTRA_KEY => ['foo' => 'bar']];

        // custom root to make sure wpstarter.json in fixtures root is not loaded

        static::assertSame(['foo' => 'bar'], $this->executeExtractConfig($extra, '/'));
    }

    /**
     * Settings form a JSON file passed as configs are loaded.
     */
    public function testConfigsLoadedFromFileIfNamePassed(): void
    {
        // @see /tests/fixtures/paths-root/custom-starter.json
        $extra = [ComposerPlugin::EXTRA_KEY => 'custom-starter.json'];

        $config = $this->executeExtractConfig($extra);

        static::assertSame('copy', $config['content-dev-op']);
        static::assertSame('./public/boot-hooks.php', $config['early-hook-file']);
    }

    /**
     * Settings form default JSON are loaded if file is found.
     */
    public function testConfigsLoadedFromDefaultFileIfThere(): void
    {
        // @see /tests/fixtures/paths-root/wpstarter.json
        $config = $this->executeExtractConfig([]);

        static::assertSame(false, $config['unknown-dropins']);
        static::assertSame('4.5.1', $config['wp-version']);
    }

    /**
     * Settings loaded from default JSON are loaded and merged with settings in extra.
     */
    public function testConfigsLoadedFromDefaultFileAreMerged(): void
    {
        $extra = [
            ComposerPlugin::EXTRA_KEY => [
                'foo' => 'bar',
                'unknown-dropins' => true,
            ],
        ];

        // @see /tests/fixtures/paths-root/wpstarter.json
        $config = $this->executeExtractConfig($extra);

        static::assertSame(false, $config['unknown-dropins'], 'File wins over extra');
        static::assertSame('bar', $config['foo']);
        static::assertSame('4.5.1', $config['wp-version']);
    }

    /**
     * For unit tests only makes sense to test the extractConfig() method, which is private.
     *
     * @param array $extra
     * @param string $customRoot
     * @return mixed
     */
    private function executeExtractConfig(array $extra, string $customRoot = null): array
    {
        $tester = \Closure::bind(
            function (string $rootPath) use ($extra): array {
                return $this->extractConfig($rootPath, $extra);
            },
            (new \ReflectionClass(Requirements::class))->newInstanceWithoutConstructor(),
            Requirements::class
        );

        return $tester($customRoot ?: $this->fixturesPath() . '/paths-root', $extra);
    }
}
