<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Io;

use WeCodeMore\WpStarter\Io\Formatter;
use WeCodeMore\WpStarter\Tests\TestCase;

class FormatterTest extends TestCase
{
    /**
     * @test
     */
    public function testEnsureLineLength(): void
    {
        $line1 = str_repeat('aa ', 40); // 120 chars
        $line2 = str_repeat('aa ', 50); // 150 chars

        $formatter = new Formatter();

        // max line length is will be 56, (`length - 4` is hardcoded to allow a 2-chars padding)
        $lines = $formatter->ensureLinesLength(60, $line1, $line2);

        $expected = [
            str_repeat('aa ', 18) . 'aa',
            str_repeat('aa ', 18) . 'aa',
            'aa aa',
            str_repeat('aa ', 18) . 'aa',
            str_repeat('aa ', 18) . 'aa',
            str_repeat('aa ', 11) . 'aa',
        ];

        static::assertSame($expected, $lines);
    }

    /**
     * @test
     */
    public function testEnsureLineLengthWithEmptyLinesEndingInsideLines(): void
    {
        $line1 = str_repeat('aa ', 40); // 120 chars
        $line2 = str_repeat('aa ', 50); // 150 chars

        $formatter = new Formatter();

        // max line length is will be 53, (length - 7 is hardcoded)
        $lines = $formatter->ensureLinesLength(60, $line1, ' ', "{$line1}\r\n{$line2}", '');

        $expected = [
            str_repeat('aa ', 18) . 'aa',
            str_repeat('aa ', 18) . 'aa',
            'aa aa',
            '',
            str_repeat('aa ', 18) . 'aa',
            str_repeat('aa ', 18) . 'aa',
            'aa aa',
            str_repeat('aa ', 18) . 'aa',
            str_repeat('aa ', 18) . 'aa',
            str_repeat('aa ', 11) . 'aa',
            '',
        ];

        static::assertSame($expected, $lines);
    }

    /**
     * @test
     */
    public function testEnsureLineLengthWithLongLines(): void
    {
        $line1 = str_repeat('a', 80); // single word 80 chars
        $line2 = str_repeat('aa ', 50); // 150 chars

        $formatter = new Formatter();

        // max line length is will be 56, (length - 4 is hardcoded)
        $lines = $formatter->ensureLinesLength(60, $line1, ' ', "{$line1}\r\n{$line2}", '');

        $expected = [
            str_repeat('a', 80),
            '',
            str_repeat('a', 80),
            str_repeat('aa ', 18) . 'aa',
            str_repeat('aa ', 18) . 'aa',
            str_repeat('aa ', 11) . 'aa',
            '',
        ];

        static::assertSame($expected, $lines);
    }

    /**
     * @test
     */
    public function testCreateFilledBlock(): void
    {
        $line1 = 'Lorem ipsum dolor sit amet';
        $line2 = 'consectetur adipiscing elit.';
        $line3 = 'Donec lorem libero, semper pellentes sodales sit amet, accumsan at nibh.';

        $formatter = new Formatter();

        $block = $formatter->createFilledBlock(' <info> ', ' </info>', $line1, $line2, $line3);

        // Max len is 56 (60 - 4) we start counting at 1 because of the leading space before <info>.
        //                    10        20        30        40        50
        //            12345678901234567890123456789012345678901234567890123456789
        $expected = [
            '',
            ' <info>                                                             </info>',
            ' <info>  Lorem ipsum dolor sit amet                                 </info>',
            ' <info>  consectetur adipiscing elit.                               </info>',
            ' <info>  Donec lorem libero, semper pellentes sodales sit amet,     </info>',
            ' <info>  accumsan at nibh.                                          </info>',
            ' <info>                                                             </info>',
            '',
        ];

        static::assertSame($expected, $block);
    }

    /**
     * @test
     */
    public function testCreateCenteredBlock(): void
    {
        $line1 = 'Lorem ipsum dolor sit amet';
        $line2 = 'consectetur adipiscing elit.';
        $line3 = 'Donec lorem libero, semper pellentes sodales sit amet, accumsan at nibh.';

        $formatter = new Formatter();

        $block = $formatter->createCenteredBlock('<error>', '</error>', $line1, $line2, '', $line3);

        // Max len is 56 (60 - 4)
        //                    10        20        30        40        50
        //            12345678901234567890123456789012345678901234567890123456789
        $expected = [
            '',
            '<error>                                                              </error>',
            '<error>                  Lorem ipsum dolor sit amet                  </error>',
            '<error>                 consectetur adipiscing elit.                 </error>',
            '<error>                                                              </error>',
            '<error>    Donec lorem libero, semper pellentes sodales sit amet,    </error>',
            '<error>                      accumsan at nibh.                       </error>',
            '<error>                                                              </error>',
            '',
        ];

        static::assertSame($expected, $block);
    }

    /**
     * @test
     */
    public function testCreateList(): void
    {
        $line1 = 'Lorem ipsum dolor sit amet';
        $line2 = 'consectetur adipiscing elit.';
        $line3 = 'Donec lorem libero, semper pellentes sodales sit amet, accumsan at nibh.';

        $formatter = new Formatter();

        $block = $formatter->createList($line1, $line2, $line3);

        //           10        20        30        40        50
        //   12345678901234567890123456789012345678901234567890123456789
        $expected = [
            ' - Lorem ipsum dolor sit amet',
            ' - consectetur adipiscing elit.',
            ' - Donec lorem libero, semper pellentes sodales sit',
            '   amet, accumsan at nibh.',
        ];

        static::assertSame($expected, $block);
    }

    /**
     * @test
     */
    public function testCreateListWithPrefix(): void
    {
        $line1 = 'Lorem ipsum dolor sit amet consectetur adipiscing elit.';
        $line2 = 'Donec lorem libero, semper pellentes sodales sit amet, accumsan at nibh.';

        $formatter = new Formatter();

        $block = $formatter->createListWithPrefix(' - <info>OK</info>', $line1, $line2);

        //                        10        20        30        40        50
        //   123      45       678901234567890123456789012345678901234567890123456789
        $expected = [
            ' - <info>OK</info> Lorem ipsum dolor sit amet consectetur adipiscing',
            '      elit.',
            ' - <info>OK</info> Donec lorem libero, semper pellentes sodales sit',
            '      amet, accumsan at nibh.',
        ];

        static::assertSame($expected, $block);
    }
}
