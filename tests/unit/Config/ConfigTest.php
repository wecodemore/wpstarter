<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Config;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Config\Result;
use WeCodeMore\WpStarter\Tests\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @test
     */
    public function testConstructor(): void
    {
        $config = new Config(
            ['foo' => 'bar', Config::ENV_EXAMPLE => 'no'],
            $this->factoryValidator()
        );

        static::assertTrue($config['foo']->is('bar'));
        static::assertTrue($config[Config::ENV_EXAMPLE]->is(false));
        static::assertTrue($config[Config::REGISTER_THEME_FOLDER]->is(false));
    }

    /**
     * @test
     */
    public function testSetNewOrDefaultConfig(): void
    {
        $config = new Config([], $this->factoryValidator());

        static::assertFalse($config['foo']->notEmpty());
        static::assertTrue($config[Config::CACHE_ENV]->notEmpty());
        static::assertTrue($config[Config::CACHE_ENV]->is(true));

        $config['foo'] = 'bar';
        $config[Config::CACHE_ENV] = false;

        /** @noinspection PhpUndefinedMethodInspection */
        static::assertTrue($config['foo']->is('bar'));
        /** @noinspection PhpUndefinedMethodInspection */
        static::assertTrue($config[Config::CACHE_ENV]->is(false));
    }

    /**
     * @test
     */
    public function testSetNewEmptyConfig(): void
    {
        $config = new Config([], $this->factoryValidator());

        $dir = str_replace('\\', '/', __DIR__);
        $config[Config::TEMPLATES_DIR] = $dir;

        /** @noinspection PhpUndefinedMethodInspection */
        static::assertTrue($config[Config::TEMPLATES_DIR]->is($dir));

        $this->expectException(\BadMethodCallException::class);
        $config[Config::TEMPLATES_DIR] = $dir;
    }

    /**
     * @test
     */
    public function testValidateCustomWithResult(): void
    {
        $config = new Config(['a' => 'hello', 'b' => 'goodbye'], $this->factoryValidator());

        $config->appendValidator('a', static function (string $value): Result {
            return $value === 'hello' ? Result::ok($value) : Result::errored('Invalid');
        });
        $config->appendValidator('b', static function (string $value): Result {
            return $value === 'hello' ? Result::ok($value) : Result::errored('Invalid');
        });

        static::assertSame('hello', $config['a']->unwrap());
        static::assertSame(-1, $config['b']->unwrapOrFallback(-1));
    }

    /**
     * @test
     */
    public function testValidateCustomIsWrappedInResult(): void
    {
        $config = new Config(['hi' => 'hello!'], $this->factoryValidator());

        $config->appendValidator('hi', static function (string $value): string {
            return strtoupper($value);
        });

        static::assertSame('HELLO!', $config['hi']->unwrap());
    }

    /**
     * @test
     */
    public function testValidateWithError(): void
    {
        $config = new Config(['hello' => 'Hello!'], $this->factoryValidator());

        $config->appendValidator('hello', static function (): void {
            throw new \Error('No hello!');
        });

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('No hello!');

        $config['hello']->unwrap();
    }

    /**
     * @test
     */
    public function testValidateWithThrowable(): void
    {
        $config = new Config(['hello' => 'Hello!'], $this->factoryValidator());

        $config->appendValidator('hello', static function (): void {
            throw new \Error('No hello!');
        });

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('No hello!');

        $config['hello']->unwrap();
    }

    /**
     * @test
     */
    public function testValidateCustomCantOverwriteDefault(): void
    {
        $config = new Config([], $this->factoryValidator());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMsgRegex('/overwrite/');

        $config->appendValidator(Config::AUTOLOAD, 'strtoupper');
    }

    /**
     * @test
     */
    public function testValidateCustomCantWarningMeansError(): void
    {
        $config = new Config(['hi' => 'hello', 'bye' => 'goodbye'], $this->factoryValidator());

        $config->appendValidator('hello', static function (): Result {
            /** @noinspection PhpDivisionByZeroInspection */
            $warning = 1 / 0;

            return Result::ok($warning > 0 ? 1 : 2);
        });

        $config->appendValidator('bye', static function (): Result {
            return Result::ok(1);
        });

        static::assertSame('Ha!', $config['hello']->unwrapOrFallback('Ha!'));
        static::assertSame(1, $config['bye']->unwrapOrFallback('Ha!'));
    }
}
