<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use Composer\Package\PackageInterface;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\PackageFinder;
use WeCodeMore\WpStarter\Util\WpVersion;

class WpVersionTest extends TestCase
{
    /**
     * @test
     * @dataProvider versionsDataProvider
     */
    public function testNormalize(string $input, string $expected): void
    {
        static::assertSame($expected, WpVersion::normalize($input));
    }

    /**
     * @test
     */
    public function testDiscoverFindsNothingIfNoPackages(): void
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $packageFinder->expects('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([]);

        $io = \Mockery::mock(Io::class);
        $io->allows('writeErrorBlock');
        $wpVer = new WpVersion($packageFinder, $io);

        static::assertSame('', $wpVer->discover());
    }

    /**
     * @test
     */
    public function testDiscoverFindsWordPressPackage(): void
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $io = \Mockery::mock(Io::class);
        $wpVer = new WpVersion($packageFinder, $io);

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->expects('getVersion')->andReturn('4.8');

        $packageFinder->expects('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([$package1]);

        static::assertSame('4.8.0', $wpVer->discover());
    }

    /**
     * @test
     */
    public function testDiscoverForDevWordPress(): void
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $io = \Mockery::mock(Io::class);
        $io->allows('writeErrorBlock');
        $wpVer = new WpVersion($packageFinder, $io);

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->expects('getVersion')->andReturn('99999-dev');

        $packageFinder->expects('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([$package1]);

        static::assertSame('99999.0.0', $wpVer->discover());
    }

    /**
     * @test
     */
    public function testDiscoverSuccessForDevNumericVersion(): void
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $io = \Mockery::mock(Io::class);
        $wpVer = new WpVersion($packageFinder, $io);

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->expects('getVersion')->andReturn('4.8-alpha1');

        $packageFinder->expects('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([$package1]);

        static::assertSame('4.8.0', $wpVer->discover());
    }

    /**
     * @test
     */
    public function testDiscoverReturnsEmptyIfWpVerIsTooOld(): void
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $io = \Mockery::mock(Io::class);
        $io->allows('writeErrorBlock');
        $wpVer = new WpVersion($packageFinder, $io);

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->expects('getVersion')->andReturn('1');

        $packageFinder->expects('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([$package1]);

        static::assertSame('', $wpVer->discover());
    }

    /**
     * @test
     */
    public function testDiscoverPrintsErrorForMoreWordPressPackages(): void
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $io = \Mockery::mock(Io::class);
        $io->allows('writeErrorBlock');
        $wpVer = new WpVersion($packageFinder, $io);

        $package1 = \Mockery::mock(PackageInterface::class);
        $package2 = clone $package1;
        $package1->expects('getVersion')->andReturn('5');
        $package2->expects('getVersion')->andReturn('5');

        $packageFinder->expects('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([$package1, $package2]);

        static::assertSame('', $wpVer->discover());
    }

    /**
     * @return list<array{string, string}>
     */
    public static function versionsDataProvider(): array
    {
        return [
            ['0', '0.0.0'],
            ['0.0', '0.0.0'],
            ['0.0.0', '0.0.0'],
            ['0.0.0.0', '0.0.0'],
            ['1', '1.0.0'],
            ['1.1', '1.1.0'],
            ['1.2.3', '1.2.3'],
            ['1.2.3.4', '1.2.3'],
            ['1-alpha', '1.0.0'],
            ['1.1-beta', '1.1.0'],
            ['1.2.3-alpha1', '1.2.3'],
            ['1.2.3-456', '1.2.3'],
            ['1.2.3.4-789', '1.2.3'],
            ['', ''],
            ['a3.5', ''],
            ['9.9.9999', '9.9.9999'],
        ];
    }
}
