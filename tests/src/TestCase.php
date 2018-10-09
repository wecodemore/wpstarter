<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::setUp();
    }

    /**
     * @return string
     */
    protected function fixturesPath(): string
    {
        return getenv('TESTS_FIXTURES_PATH');
    }

    /**
     * @param array $extra
     * @param string $vendorDir
     * @param string $binDir
     * @return \WeCodeMore\WpStarter\Config\Validator
     */
    protected function makeValidator(
        array $extra = [],
        string $vendorDir = __DIR__,
        string $binDir = __DIR__
    ): \WeCodeMore\WpStarter\Config\Validator {

        $config = \Mockery::mock(\Composer\Config::class);
        $config->shouldReceive('get')->with('vendor-dir')->andReturn($vendorDir);
        $config->shouldReceive('get')->with('bin-dir')->andReturn($binDir);
        $composer = \Mockery::mock(\Composer\Composer::class);
        $composer->shouldReceive('getConfig')->andReturn($config);
        $composer->shouldReceive('getPackage->getExtra')->andReturn($extra);

        $filesystem = new \Composer\Util\Filesystem();

        $paths = new \WeCodeMore\WpStarter\Util\Paths($composer, $filesystem);

        return new \WeCodeMore\WpStarter\Config\Validator($paths, $filesystem);
    }
}
