<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use Composer\Package\CompletePackage;
use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Util\MuPluginList;
use WeCodeMore\WpStarter\Util\PackageFinder;

class MuPluginListTest extends TestCase
{
    /**
     * @test
     */
    public function testPluginList(): void
    {
        $package1 = new CompletePackage('test/mu-plugin-1', '1.0.0.0', '1');
        $package1->setType('wordpress-muplugin');

        $package2 = new CompletePackage('test/mu-plugin-2', '2.0.0.0', '2');
        $package2->setType('wordpress-muplugin');

        $muPluginsPath = $this->fixturesPath() . '/paths-root/public/wp-content/mu-plugins';

        $finder = \Mockery::mock(PackageFinder::class);
        $finder
            ->expects('findByType')
            ->once()
            ->with('wordpress-muplugin')
            ->andReturn([$package1, $package2]);

        $finder
            ->expects('findPathOf')
            ->once()
            ->with($package1)
            ->andReturn("{$muPluginsPath}/dir1");
        $finder
            ->expects('findPathOf')
            ->once()
            ->with($package2)
            ->andReturn("{$muPluginsPath}/dir2");

        $expected = [
            'test/mu-plugin-1' => "{$muPluginsPath}/dir1/mu-plugin.php",
            'test/mu-plugin-2_a-mu-plugin' => "{$muPluginsPath}/dir2/a-mu-plugin.php",
            'test/mu-plugin-2_b-mu-plugin' => "{$muPluginsPath}/dir2/b-mu-plugin.php",
        ];

        $muPluginsList = new MuPluginList($finder, $this->factoryPaths(), new Filesystem());
        $actual = $muPluginsList->pluginsList($this->factoryConfig());

        ksort($expected);
        ksort($actual);

        static::assertSame($expected, $actual);
    }
}
