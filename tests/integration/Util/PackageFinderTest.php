<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Integration\Util;

use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Util\PackageFinder;

class PackageFinderTest extends IntegrationTestCase
{
    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Util\PackageFinder
     */
    public function testFindByType(): void
    {
        $finder = $this->factoryFinder();

        $plugins = $finder->findByType('composer-plugin');
        $names = [];

        foreach ($plugins as $plugin) {
            static::assertInstanceOf(PackageInterface::class, $plugin);
            $names[] = $plugin->getName();
        }

        static::assertCount(2, $names);
        static::assertTrue(in_array('composer/installers', $names, true));
        static::assertTrue(
            in_array('dealerdirect/phpcodesniffer-composer-installer', $names, true)
        );
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Util\PackageFinder
     */
    public function testFindPathOf(): void
    {
        $finder = $this->factoryFinder();

        $phpcsInstaller = $finder->findByName('dealerdirect/phpcodesniffer-composer-installer');

        static::assertInstanceOf(PackageInterface::class, $phpcsInstaller);

        $path = $finder->findPathOf($phpcsInstaller);
        $paths = explode('/vendor/', $path);

        $expectedVendor = str_replace('\\', '/', $this->factoryComposerConfig()->get('vendor-dir'));

        static::assertCount(2, $paths);
        static::assertSame("{$paths[0]}/vendor", $expectedVendor);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Util\PackageFinder
     */
    public function testFindByVendor(): void
    {
        $finder = $this->factoryFinder();

        $roavePackages = $finder->findByVendor('phpunit');

        $names = [];
        foreach ($roavePackages as $package) {
            static::assertInstanceOf(PackageInterface::class, $package);
            $names[] = $package->getName();
        }

        static::assertTrue(in_array('phpunit/phpunit', $names, true));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Util\PackageFinder
     */
    public function testFindByName(): void
    {
        $finder = $this->factoryFinder();

        $phpcs = $finder->findByName('*/*_c*er*');

        static::assertInstanceOf(PackageInterface::class, $phpcs);
        static::assertSame('squizlabs/php_codesniffer', $phpcs->getName());
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Util\PackageFinder
     */
    public function testSearch(): void
    {
        $finder = $this->factoryFinder();

        $phpcsPackages = $finder->search('*l*/php*c*er*');
        $names = [];
        foreach ($phpcsPackages as $package) {
            static::assertInstanceOf(PackageInterface::class, $package);
            $names[] = $package->getName();
        }

        static::assertCount(2, $names);
        static::assertTrue(in_array('squizlabs/php_codesniffer', $names, true));
        static::assertTrue(
            in_array('dealerdirect/phpcodesniffer-composer-installer', $names, true)
        );
    }

    /**
     * @return PackageFinder
     */
    private function factoryFinder(): PackageFinder
    {
        $composer = $this->factoryComposer();

        return new PackageFinder(
            $composer->getRepositoryManager()->getLocalRepository(),
            $composer->getInstallationManager(),
            new Filesystem()
        );
    }
}
