<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Config;

use WeCodeMore\WpStarter\Config\Result;
use WeCodeMore\WpStarter\Tests\TestCase;

class ResultTest extends TestCase
{
    public function testOk()
    {
        $result = Result::ok('ok!');

        static::assertSame('ok!', $result->unwrapOrFallback('meh'));
        static::assertSame('ok!', $result->unwrapOrBail());
        static::assertTrue($result->is('ok!'));
        static::assertFalse($result->is('no!'));
        static::assertFalse($result->not('ok!'));
        static::assertTrue($result->not('no!'));
        static::assertTrue($result->notEmpty());
        static::assertTrue($result->either('no!', 'ok!'));
        static::assertFalse($result->either('no!', 'no!'));
    }

    public function testNone()
    {
        $result = Result::none();

        static::assertSame(null, $result->unwrapOrFallback('meh'));
        static::assertSame(null, $result->unwrapOrBail());
        static::assertFalse($result->is('ok!'));
        static::assertTrue($result->not('ok!'));
        static::assertFalse($result->either('no!', 'ok!'));
    }

    public function testError()
    {
        $result = Result::error();

        static::assertSame('meh', $result->unwrapOrFallback('meh'));
        static::assertFalse($result->is('ok!'));
        static::assertTrue($result->not('ok!'));
        static::assertTrue($result->not('no!'));
        static::assertFalse($result->notEmpty());
        static::assertFalse($result->either('no!', 'ok!'));
    }

    public function testErrorBail()
    {
        $result = Result::error();

        $this->expectException(\Error::class);
        $result->unwrapOrBail();
    }

    public function testErroredBail()
    {
        $result = Result::errored('Meh!');

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Meh!');
        $result->unwrapOrBail();
    }
}