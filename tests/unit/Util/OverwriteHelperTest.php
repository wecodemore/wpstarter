<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Step\OptionalStep;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\OverwriteHelper;

class OverwriteHelperTest extends TestCase
{
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

        $config = new Config($configs, $this->makeValidator());
        $io = \Mockery::mock(Io::class);
        $io->shouldReceive('askConfirm')
            ->with(\Mockery::type('array'), basename($file))
            ->andReturn($confirm);

        return new OverwriteHelper($config, $io, dirname(__DIR__), new Filesystem());
    }

    public function testShouldOverwriteReturnTrueIfFileNotExists()
    {
        $helper = $this->makeHelper([Config::PREVENT_OVERWRITE => true]);

        static::assertTrue($helper->shouldOverwite(__DIR__ . '/foo.bar'));
    }

    public function testShouldOverwriteReturnTrueIfConfigIsTrue()
    {
        $helper = $this->makeHelper([Config::PREVENT_OVERWRITE => true]);

        static::assertFalse($helper->shouldOverwite(__FILE__));
    }

    public function testShouldOverwriteReturnFalseIfConfigIsFalse()
    {
        $helper = $this->makeHelper([Config::PREVENT_OVERWRITE => false]);

        static::assertTrue($helper->shouldOverwite(__FILE__));
    }

    public function testShouldOverwriteReturnTrueIfConfirmationAskedReturnsTrue()
    {
        $helper = $this->makeHelper(
            [Config::PREVENT_OVERWRITE => OptionalStep::ASK],
            true,
            __FILE__
        );

        static::assertTrue($helper->shouldOverwite(__FILE__));
    }

    public function testShouldOverwriteReturnFalseIfConfirmationAskedReturnsFalse()
    {
        $helper = $this->makeHelper(
            [Config::PREVENT_OVERWRITE => OptionalStep::ASK],
            false,
            __FILE__
        );

        static::assertFalse($helper->shouldOverwite(__FILE__));
    }

    public function testShouldOverwriteWithPatternMatch()
    {
        $fileName = pathinfo(__FILE__, PATHINFO_FILENAME);

        $helper1 = $this->makeHelper([Config::PREVENT_OVERWRITE => ['Util/*.php']]);
        $helper2 = $this->makeHelper([Config::PREVENT_OVERWRITE => ["*/{$fileName}.*"]]);
        $helper3 = $this->makeHelper([Config::PREVENT_OVERWRITE => ["*/{$fileName}.txt"]]);
        $helper4 = $this->makeHelper([Config::PREVENT_OVERWRITE => ["{$fileName}.*"]]);
        $helper5 = $this->makeHelper([Config::PREVENT_OVERWRITE => ['./Util/*.php']]);
        $helper6 = $this->makeHelper([Config::PREVENT_OVERWRITE => ['*/*.*']]);

        static::assertFalse($helper1->shouldOverwite(__FILE__));
        static::assertFalse($helper2->shouldOverwite(__FILE__));
        static::assertTrue($helper3->shouldOverwite(__FILE__));
        static::assertTrue($helper4->shouldOverwite(__FILE__));
        static::assertFalse($helper5->shouldOverwite(__FILE__));

        static::assertFalse($helper6->shouldOverwite($this->packagePath().'/composer.json'));
        static::assertFalse($helper6->shouldOverwite(__DIR__));
        static::assertTrue($helper6->shouldOverwite("{$fileName}.txt"));
        static::assertFalse($helper6->shouldOverwite(__FILE__));
    }
}
