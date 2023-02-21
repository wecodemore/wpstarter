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
        $salter = new Salter();
        $keys = $salter->keys();

        foreach (Salter::KEYS as $key) {
            static::assertArrayHasKey($key, $keys);
            static::assertIsString($keys[$key]);
            static::assertSame(64, strlen($keys[$key]));
        }
    }
}
