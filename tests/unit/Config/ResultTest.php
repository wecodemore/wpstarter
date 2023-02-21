<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Config;

use WeCodeMore\WpStarter\Config\Result;
use WeCodeMore\WpStarter\Tests\TestCase;

class ResultTest extends TestCase
{
    /**
     * @test
     */
    public function testIdentityInConstructor(): void
    {
        $result = Result::ok('ok!');
        $result2 = Result::ok($result);

        $error = Result::errored('Meh!');
        $error2 = Result::ok($error);

        static::assertTrue($result2->is('ok!'));

        $this->expectExceptionMessage('Meh!');
        $error2->unwrap();
    }

    /**
     * @test
     */
    public function testOk(): void
    {
        $result = Result::ok('ok!');

        static::assertSame('ok!', $result->unwrapOrFallback('meh'));
        static::assertSame('ok!', $result->unwrap());
        static::assertTrue($result->is('ok!'));
        static::assertFalse($result->is('no!'));
        static::assertFalse($result->not('ok!'));
        static::assertTrue($result->not('no!'));
        static::assertTrue($result->notEmpty());
        static::assertTrue($result->either('no!', 'ok!'));
        static::assertFalse($result->either('no!', 'no!'));
    }

    /**
     * @test
     */
    public function testNone(): void
    {
        $result = Result::none();

        static::assertSame('meh', $result->unwrapOrFallback('meh'));
        static::assertSame(null, $result->unwrap());
        static::assertFalse($result->is('ok!'));
        static::assertTrue($result->not('ok!'));
        static::assertFalse($result->either('no!', 'ok!'));
    }

    /**
     * @test
     */
    public function testError(): void
    {
        $result = Result::error();

        static::assertSame('meh', $result->unwrapOrFallback('meh'));
        static::assertFalse($result->is('ok!'));
        static::assertTrue($result->not('ok!'));
        static::assertTrue($result->not('no!'));
        static::assertFalse($result->notEmpty());
        static::assertFalse($result->either('no!', 'ok!'));
    }

    /**
     * @test
     */
    public function testErrorBail(): void
    {
        $result = Result::error();

        $this->expectException(\Error::class);
        $result->unwrap();
    }

    /**
     * @test
     */
    public function testErroredBail(): void
    {
        $result = Result::errored('Meh!');

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Meh!');
        $result->unwrap();
    }
}
