<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Util\Salter;

class SalterTest extends TestCase
{
    /**
     * @test
     */
    public function testKeys(): void
    {
        $length = random_int(32, 128);

        $keys = (new Salter($length))->keys();

        foreach (Salter::KEYS as $key) {
            static::assertSame($length, strlen($keys[$key] ?? ''));
        }
    }

    /**
     * @test
     */
    public function testMinCharactersIs8(): void
    {
        $keys = (new Salter(1))->keys();

        foreach (Salter::KEYS as $key) {
            static::assertSame(8, strlen($keys[$key] ?? ''));
        }
    }

    /**
     * @test
     */
    public function testMaxCharactersIs256(): void
    {
        $keys = (new Salter(1024))->keys();

        foreach (Salter::KEYS as $key) {
            static::assertSame(256, strlen($keys[$key] ?? ''));
        }
    }
}
