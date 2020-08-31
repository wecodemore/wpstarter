<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Config;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Config\Error;
use WeCodeMore\WpStarter\Config\Result;
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
        static::assertTrue($config[Config::REGISTER_THEME_FOLDER]->is(false));
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

    /**
     * @see TestCase::makeValidator()
     */
    public function testValidateCustomWithResult()
    {
        $config = new Config(['a' => 'hello', 'b' => 'goodbye'], $this->makeValidator());

        $config->appendValidator('a', function (string $value): Result {
            return $value === 'hello' ? Result::ok($value) : Result::errored('Invalid');
        });
        $config->appendValidator('b', function (string $value): Result {
            return $value === 'hello' ? Result::ok($value) : Result::errored('Invalid');
        });

        static::assertSame('hello', $config['a']->unwrap());
        static::assertSame(-1, $config['b']->unwrapOrFallback(-1));
    }

    /**
     * @see TestCase::makeValidator()
     */
    public function testValidateCustomIsWrappedInResult()
    {
        $config = new Config(['hi' => 'hello!'], $this->makeValidator());

        $config->appendValidator('hi', function (string $value): string {
            return strtoupper($value);
        });

        static::assertSame('HELLO!', $config['hi']->unwrap());
    }

    /**
     * @see TestCase::makeValidator()
     */
    public function testValidateWithError()
    {
        $config = new Config(['hello' => 'Hello!'], $this->makeValidator());

        $config->appendValidator('hello', function () {
            throw new \Error('No hello!');
        });

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('No hello!');

        $config['hello']->unwrap();
    }

    /**
     * @see TestCase::makeValidator()
     */
    public function testValidateWithThrowable()
    {
        $config = new Config(['hello' => 'Hello!'], $this->makeValidator());

        $config->appendValidator('hello', function () {
            throw new \Error('No hello!');
        });

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('No hello!');

        $config['hello']->unwrap();
    }

    /**
     * @see TestCase::makeValidator()
     */
    public function testValidateCustomCantOverwriteDefault()
    {
        $config = new Config([], $this->makeValidator());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/overwrite/');

        $config->appendValidator(Config::AUTOLOAD, 'strtoupper');
    }

    /**
     * @see TestCase::makeValidator()
     */
    public function testValidateCustomCantWarningMeansError()
    {
        $config = new Config(['hi' => 'hello', 'bye' => 'goodbye'], $this->makeValidator());

        $config->appendValidator('hello', function (): Result {
            $warning = 1/0;

            return Result::ok($warning > 0 ? 1 : 2);
        });

        $config->appendValidator('bye', function (): Result {
            $noWarning = 1/1;

            return Result::ok($noWarning > 0 ? 1 : 2);
        });

        static::assertSame('Ha!', $config['hello']->unwrapOrFallback('Ha!'));
        static::assertSame(1, $config['bye']->unwrapOrFallback('Ha!'));
    }
}
