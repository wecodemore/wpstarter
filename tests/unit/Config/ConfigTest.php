<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Config;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Tests\TestCase;

class ConfigTest extends TestCase
{
    public function testConstructor()
    {
        $config = new Config(
            ['foo' => 'bar', Config::ENV_EXAMPLE => 'no'],
            $this->makeValidator()
        );

        static::assertTrue($config['foo']->is('bar'));
        static::assertTrue($config[Config::ENV_EXAMPLE]->is(false));
        static::assertTrue($config[Config::REGISTER_THEME_FOLDER]->is(true));
    }

    public function testSetNewOrDefaultConfig()
    {
        $config = new Config([], $this->makeValidator());

        static::assertFalse($config['foo']->notEmpty());
        static::assertTrue($config[Config::CACHE_ENV]->notEmpty());
        static::assertTrue($config[Config::CACHE_ENV]->is(true));

        $config['foo'] = 'bar';
        $config[Config::CACHE_ENV] = false;

        static::assertTrue($config['foo']->is('bar'));
        static::assertTrue($config[Config::CACHE_ENV]->is(false));
    }

    public function testSetNewEmptyConfig()
    {
        $config = new Config([], $this->makeValidator());

        $dir = str_replace('\\', '/', __DIR__);
        $config[Config::TEMPLATES_DIR] = $dir;

        static::assertTrue($config[Config::TEMPLATES_DIR]->is($dir));

        $this->expectException(\BadMethodCallException::class);
        $config[Config::TEMPLATES_DIR] = $dir;
    }
}
