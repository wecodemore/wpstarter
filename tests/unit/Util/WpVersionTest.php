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
        $wpVer = new WpVersion(\Mockery::mock(IOInterface::class));

        static::assertSame('', $wpVer->discover());
    }

    public function testDiscoverFindsNothingIfWrongPackageTypes()
    {
        $wpVer = new WpVersion(\Mockery::mock(IOInterface::class));

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->shouldReceive('getType')->andReturn('library');

        $package2 = \Mockery::mock(PackageInterface::class);
        $package2->shouldReceive('getType')->andReturn('wordpress-plugin');

        static::assertSame('', $wpVer->discover($package1, $package2));
    }

    public function testDiscoverFindsWordPressPackage()
    {
        $wpVer = new WpVersion(\Mockery::mock(IOInterface::class));

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->shouldReceive('getType')->andReturn('wordpress-core');
        $package1->shouldReceive('getVersion')->andReturn('4.8');

        $package2 = \Mockery::mock(PackageInterface::class);
        $package2->shouldReceive('getType')->andReturn('wordpress-plugin');

        static::assertSame('4.8.0', $wpVer->discover($package1, $package2));
    }

    public function testDiscoverReturnsEmptyIfWpVerIsTooOld()
    {
        $wpVer = new WpVersion(\Mockery::mock(IOInterface::class));

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->shouldReceive('getType')->andReturn('wordpress-core');
        $package1->shouldReceive('getVersion')->andReturn('1');

        $package2 = \Mockery::mock(PackageInterface::class);
        $package2->shouldReceive('getType')->andReturn('wordpress-plugin');

        static::assertSame('', $wpVer->discover($package1, $package2));
    }

    public function testDiscoverPrintsErrorForMoreWordPressPackages()
    {
        $io = \Mockery::mock(IOInterface::class);
        $io->shouldReceive('writeError')
            ->once()
            ->with(\Mockery::type('array'));

        $wpVer = new WpVersion($io);

        $package1 = \Mockery::mock(PackageInterface::class);
        $package1->shouldReceive('getType')->andReturn('wordpress-core');
        $package1->shouldReceive('getVersion')->andReturn('5');

        $package2 = clone $package1;

        static::assertSame('', $wpVer->discover($package1, $package2));
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
