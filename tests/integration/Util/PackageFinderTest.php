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

        static::assertCount(3, $names);
        static::assertTrue(in_array('composer/installers', $names, true));
        static::assertTrue(in_array('composer/package-versions-deprecated', $names, true));
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

        $phpunitPackage = $finder->findByName('phpunit/phpunit');

        static::assertInstanceOf(PackageInterface::class, $phpunitPackage);

        $path = $finder->findPathOf($phpunitPackage);
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

        $phpunitPackages = $finder->findByVendor('phpunit');

        $names = [];
        foreach ($phpunitPackages as $package) {
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

        $phpunitPackage = $finder->findByName('*nit/p*p*n*t');

        static::assertInstanceOf(PackageInterface::class, $phpunitPackage);
        static::assertSame('phpunit/phpunit', $phpunitPackage->getName());
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Util\PackageFinder
     */
    public function testSearch(): void
    {
        $finder = $this->factoryFinder();

        $phpcsPackages = $finder->search('*it/php*');
        $names = [];
        foreach ($phpcsPackages as $package) {
            static::assertInstanceOf(PackageInterface::class, $package);
            $names[] = $package->getName();
        }

        static::assertTrue(in_array('phpunit/phpunit', $names, true));
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
