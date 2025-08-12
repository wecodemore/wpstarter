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
    /**
     * @test
     */
    public function testGenericCommandInstanceCreation(): void
    {
        $config = $this->factoryRequirements()->config();

        static::assertTrue($config[Config::IS_WPSTARTER_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_WPSTARTER_SELECTED_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_UPDATE]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_INSTALL]->unwrap());
    }

    /**
     * @test
     */
    public function testSelectedStepsCommandInstanceCreation(): void
    {
        [$composer, $io, $filesystem] = $this->factoryRequirementsDependencies();

        $requirements = Requirements::forSelectedStepsCommand($composer, $io, $filesystem);
        $config = $requirements->config();

        static::assertTrue($config[Config::IS_WPSTARTER_COMMAND]->unwrap());
        static::assertTrue($config[Config::IS_WPSTARTER_SELECTED_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_UPDATE]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_INSTALL]->unwrap());
    }

    /**
     * @test
     */
    public function testComposerInstallInstanceCreation(): void
    {
        [$composer, $io, $filesystem] = $this->factoryRequirementsDependencies();

        $pkg1 = new Package('one', '1.0.0.0', '1.0.0');
        $pkg2 = new Package('two', '2.0.0.0', '2.0');
        $requirements = Requirements::forComposerInstall($composer, $io, $filesystem, $pkg1, $pkg2);
        $config = $requirements->config();

        static::assertFalse($config[Config::IS_WPSTARTER_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_WPSTARTER_SELECTED_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_UPDATE]->unwrap());
        static::assertTrue($config[Config::IS_COMPOSER_INSTALL]->unwrap());
        static::assertSame([$pkg1, $pkg2], $config[Config::COMPOSER_UPDATED_PACKAGES]->unwrap());
    }

    /**
     * @test
     */
    public function testComposerUpdateInstanceCreation(): void
    {
        [$composer, $io, $filesystem] = $this->factoryRequirementsDependencies();

        $requirements = Requirements::forComposerUpdate($composer, $io, $filesystem);
        $config = $requirements->config();

        static::assertFalse($config[Config::IS_WPSTARTER_COMMAND]->unwrap());
        static::assertFalse($config[Config::IS_WPSTARTER_SELECTED_COMMAND]->unwrap());
        static::assertTrue($config[Config::IS_COMPOSER_UPDATE]->unwrap());
        static::assertFalse($config[Config::IS_COMPOSER_INSTALL]->unwrap());
        static::assertSame([], $config[Config::COMPOSER_UPDATED_PACKAGES]->unwrap());
    }

    /**
     * @test
     */
    public function testEnsureConfigAreAllDefaultWhenThereIsNoExtraValue(): void
    {
        // This will fail because being paths that does not exists wil fail validation.
        $expectedFailures = [
            Config::AUTOLOAD,
            Config::CONTENT_DEV_DIR,
            Config::EARLY_HOOKS_FILE,
            Config::ENV_BOOTSTRAP_DIR,
            Config::ENV_DIR,
            Config::TEMPLATES_DIR,
        ];

        // These keys will not match Config defaults because changed by requirements.
        $byRequirements = [
            Config::IS_COMPOSER_UPDATE => false,
            Config::IS_COMPOSER_INSTALL => false,
            Config::IS_WPSTARTER_COMMAND => true,
            Config::IS_WPSTARTER_SELECTED_COMMAND => false,
        ];

        $config = $this->factoryRequirements()->config();
        foreach (Config::DEFAULTS as $key => $value) {
            if (in_array($key, $expectedFailures, true)) {
                $rand = bin2hex(random_bytes(12));
                static::assertSame($rand, $config->offsetGet($key)->unwrapOrFallback($rand));
                continue;
            }
            if (isset($byRequirements[$key])) {
                static::assertSame($byRequirements[$key], $config->offsetGet($key)->unwrap());
                continue;
            }
            static::assertSame($value, $config->offsetGet($key)->unwrap());
        }
    }

    /**
     * @test
     */
    public function testCustomDataIsPreserved(): void
    {
        $extra = [ComposerPlugin::EXTRA_KEY => ['foo' => 'bar']];

        $config = $this->factoryRequirements($extra)->config();

        static::assertTrue($config['foo']->is('bar'));

        // default values are there as well...
        static::assertTrue($config[Config::CONTENT_DEV_OPERATION]->is('symlink'));
    }

    /**
     * @test
     */
    public function testConfigsLoadedFromFileIfNamePassed(): void
    {
        $extra = [ComposerPlugin::EXTRA_KEY => 'custom-starter.json'];
        $root = $this->fixturesPath() . '/paths-root';

        $config = $this->factoryRequirements($extra, $root)->config();

        /** @see /tests/fixtures/paths-root/custom-starter.json */
        static::assertTrue($config[Config::CONTENT_DEV_OPERATION]->is('copy'));
        static::assertTrue($config[Config::ENV_FILE]->is('my.env'));
        static::assertTrue($config[Config::REQUIRE_WP]->is(false));
        static::assertTrue($config[Config::MOVE_CONTENT]->is(true));
    }

    /**
     * @test
     */
    public function testConfigsLoadedFromDefaultFileIfThere(): void
    {
        $root = $this->fixturesPath() . '/paths-root';
        $config = $this->factoryRequirements([], $root)->config();

        /** @see /tests/fixtures/paths-root/wpstarter.json */
        static::assertTrue($config[Config::UNKNOWN_DROPINS]->is(false));
        static::assertTrue($config[Config::WP_VERSION]->is('4.5.1'));
    }

    /**
     * @test
     */
    public function testConfigsLoadedFromDefaultFileAreMerged(): void
    {
        $extra = [
            ComposerPlugin::EXTRA_KEY => [
                'foo' => 'bar',
                'unknown-dropins' => true,
            ],
        ];

        $root = $this->fixturesPath() . '/paths-root';
        $config = $this->factoryRequirements($extra, $root)->config();

        /** @see /tests/fixtures/paths-root/wpstarter.json */
        static::assertTrue($config[Config::UNKNOWN_DROPINS]->is(false), 'File win over extra');
        static::assertTrue($config[Config::WP_VERSION]->is('4.5.1'));
    }

    /**
     * @param array<mixed> $extra
     * @param non-falsy-string|null $root
     * @return Requirements
     */
    private function factoryRequirements(array $extra = [], ?string $root = null): Requirements
    {
        [$composer, $io, $filesystem] = $this->factoryRequirementsDependencies($extra);
        if ($root === null) {
            return Requirements::forGenericCommand($composer, $io, $filesystem);
        }

        return Requirements::forCustomRoot($composer, $io, $filesystem, $root);
    }

    /**
     * @return array{\Composer\Composer, \Composer\IO\IOInterface, \Composer\Util\Filesystem}
     */
    private function factoryRequirementsDependencies(array $extra = []): array
    {
        $composerConfig = \Mockery::mock(\Composer\Config::class);
        $composerConfig->allows('get')->andReturn('');

        $composer = \Mockery::mock(\Composer\Composer::class);
        $composer->allows('getPackage->getExtra')->andReturn($extra);
        $composer->allows('getConfig')->andReturn($composerConfig);

        $io = new NullIO();
        $filesystem = new \Composer\Util\Filesystem();

        return [$composer, $io, $filesystem];
    }
}
