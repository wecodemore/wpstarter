<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Step\OptionalStep;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\OverwriteHelper;

class OverwriteHelperTest extends TestCase
{
    /**
     * @test
     */
    public function testShouldOverwriteReturnTrueIfFileNotExists(): void
    {
        $helper = $this->makeHelper([Config::PREVENT_OVERWRITE => true]);

        static::assertTrue($helper->shouldOverwrite(__DIR__ . '/foo.bar'));
    }

    /**
     * @test
     */
    public function testShouldOverwriteReturnTrueIfConfigIsTrue(): void
    {
        $helper = $this->makeHelper([Config::PREVENT_OVERWRITE => true]);

        static::assertFalse($helper->shouldOverwrite(__FILE__));
    }

    /**
     * @test
     */
    public function testShouldOverwriteReturnFalseIfConfigIsFalse(): void
    {
        $helper = $this->makeHelper([Config::PREVENT_OVERWRITE => false]);

        static::assertTrue($helper->shouldOverwrite(__FILE__));
    }

    /**
     * @test
     */
    public function testShouldOverwriteReturnTrueIfConfirmationAskedReturnsTrue(): void
    {
        $helper = $this->makeHelper(
            [Config::PREVENT_OVERWRITE => OptionalStep::ASK],
            true,
            __FILE__
        );

        static::assertTrue($helper->shouldOverwrite(__FILE__));
    }

    /**
     * @test
     */
    public function testShouldOverwriteReturnFalseIfConfirmationAskedReturnsFalse(): void
    {
        $helper = $this->makeHelper(
            [Config::PREVENT_OVERWRITE => OptionalStep::ASK],
            false,
            __FILE__
        );

        static::assertFalse($helper->shouldOverwrite(__FILE__));
    }

    /**
     * @test
     */
    public function testShouldOverwriteWithPatternMatch(): void
    {
        $fileName = pathinfo(__FILE__, PATHINFO_FILENAME);

        $helper1 = $this->makeHelper([Config::PREVENT_OVERWRITE => ['Util/*.php']]);
        $helper2 = $this->makeHelper([Config::PREVENT_OVERWRITE => ["*/{$fileName}.*"]]);
        $helper3 = $this->makeHelper([Config::PREVENT_OVERWRITE => ["*/{$fileName}.txt"]]);
        $helper4 = $this->makeHelper([Config::PREVENT_OVERWRITE => ["{$fileName}.*"]]);
        $helper5 = $this->makeHelper([Config::PREVENT_OVERWRITE => ['./Util/*.php']]);
        $helper6 = $this->makeHelper([Config::PREVENT_OVERWRITE => ['*/*.*']]);

        static::assertFalse($helper1->shouldOverwrite(__FILE__));
        static::assertFalse($helper2->shouldOverwrite(__FILE__));
        static::assertTrue($helper3->shouldOverwrite(__FILE__));
        static::assertTrue($helper4->shouldOverwrite(__FILE__));
        static::assertFalse($helper5->shouldOverwrite(__FILE__));

        static::assertFalse($helper6->shouldOverwrite($this->packagePath() . '/composer.json'));
        static::assertFalse($helper6->shouldOverwrite(__DIR__));
        static::assertTrue($helper6->shouldOverwrite("{$fileName}.txt"));
        static::assertFalse($helper6->shouldOverwrite(__FILE__));
    }

    /**
     * @param array $configs
     * @param bool $confirm
     * @param string $file
     * @return OverwriteHelper
     */
    private function makeHelper(
        array $configs = [],
        bool $confirm = true,
        string $file = ''
    ): OverwriteHelper {

        $config = new Config($configs, $this->factoryValidator());
        $io = \Mockery::mock(Io::class);
        $io->allows('askConfirm')
            ->with(\Mockery::type('array'), basename($file))
            ->andReturn($confirm);

        return new OverwriteHelper($config, $io, dirname(__DIR__), new Filesystem());
    }
}
