<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\PackageFinder;
use WeCodeMore\WpStarter\Util\WpVersion;

class WpVersionTest extends TestCase
{
    /**
     * @dataProvider versionsDataProvider
     * @param string $input
     * @param string $expected
     */
    public function testNormalize(string $input, string $expected)
    {
        static::assertSame($expected, WpVersion::normalize($input));
    }

    public function testDiscoverFindsNothingIfNoPackages()
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $packageFinder->shouldReceive('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([]);

        $io = \Mockery::mock(Io::class);
        $io->shouldReceive('writeErrorBlock');
        $wpVer = new WpVersion($packageFinder, $io);

        static::assertSame('', $wpVer->discover());
    }

    public function testDiscoverFindsWordPressPackage()
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $io = \Mockery::mock(Io::class);
        $wpVer = new WpVersion($packageFinder, $io);

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->shouldReceive('getType')->andReturn('wordpress-core');
        $package1->shouldReceive('getVersion')->andReturn('4.8');
        $package1->shouldReceive('isDev')->andReturn(false);

        $packageFinder->shouldReceive('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([$package1]);

        static::assertSame('4.8.0', $wpVer->discover());
    }

    public function testDiscoverFailForDevWordPress()
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $io = \Mockery::mock(Io::class);
        $io->shouldReceive('writeErrorBlock');
        $wpVer = new WpVersion($packageFinder, $io);

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->shouldReceive('getType')->andReturn('wordpress-core');
        $package1->shouldReceive('getVersion')->andReturn('99999-dev');
        $package1->shouldReceive('isDev')->andReturn(true);

        $packageFinder->shouldReceive('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([$package1]);

        static::assertSame('', $wpVer->discover());
    }

    public function testDiscoverSuccessForDevNumericVersion()
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $io = \Mockery::mock(Io::class);
        $wpVer = new WpVersion($packageFinder, $io);

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->shouldReceive('getType')->andReturn('wordpress-core');
        $package1->shouldReceive('getVersion')->andReturn('4.8-alpha1');
        $package1->shouldReceive('isDev')->andReturn(true);

        $packageFinder->shouldReceive('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([$package1]);

        static::assertSame('4.8.0', $wpVer->discover());
    }

    public function testDiscoverReturnsEmptyIfWpVerIsTooOld()
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $io = \Mockery::mock(Io::class);
        $io->shouldReceive('writeErrorBlock');
        $wpVer = new WpVersion($packageFinder, $io);

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->shouldReceive('getType')->andReturn('wordpress-core');
        $package1->shouldReceive('getVersion')->andReturn('1');
        $package1->shouldReceive('isDev')->andReturn(false);

        $packageFinder->shouldReceive('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([$package1]);

        static::assertSame('', $wpVer->discover());
    }

    public function testDiscoverPrintsErrorForMoreWordPressPackages()
    {
        $packageFinder = \Mockery::mock(PackageFinder::class);
        $io = \Mockery::mock(Io::class);
        $io->shouldReceive('writeErrorBlock');
        $wpVer = new WpVersion($packageFinder, $io);

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->shouldReceive('getType')->andReturn('wordpress-core');
        $package1->shouldReceive('getVersion')->andReturn('5');
        $package1->shouldReceive('isDev')->andReturn(false);

        $package2 = clone $package1;

        $packageFinder->shouldReceive('findByType')
            ->with(WpVersion::WP_PACKAGE_TYPE)
            ->andReturn([$package1, $package2]);

        static::assertSame('', $wpVer->discover());
    }

    /**
     * @return array[]
     */
    public function versionsDataProvider(): array
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
            ['10', ''],
            ['10.0.1', ''],
            ['1.11', ''],
            ['a3.5', ''],
            ['9.9.9999', '9.9.9999'],
        ];
    }
}
