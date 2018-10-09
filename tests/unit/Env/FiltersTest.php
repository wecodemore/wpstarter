<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Env;

use WeCodeMore\WpStarter\Env\Filters;
use WeCodeMore\WpStarter\Tests\TestCase;

class FiltersTest extends TestCase
{
    /**
     * @dataProvider filterDataProvider
     *
     * @param string $mode
     * @param $input
     * @param $expectedOutput
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    public function testFilter(string $mode, $input, $expectedOutput)
    {
        // phpcs:enable

        $filter = new Filters();

        $describe = preg_replace(
            '~\s+~',
            ' ',
            var_export(compact('mode', 'input', 'expectedOutput'), true) // phpcs:ignore
        );

        static::assertSame(
            $expectedOutput,
            $filter->filter($mode, $input),
            sprintf('Failed for: {%s}.', str_replace(['array ( ', ', )'], '', $describe))
        );
    }

    /**
     * @return array[]
     *
     * phpcs:disable Inpsyde.CodeQuality.FunctionLength
     */
    public function filterDataProvider(): array
    {
        // phpcs:enable

        return [
            [Filters::FILTER_BOOL, 1, true],
            [Filters::FILTER_BOOL, 'true', true],
            [Filters::FILTER_BOOL, 'yes', true],
            [Filters::FILTER_BOOL, 'on', true],
            [Filters::FILTER_BOOL, 0, false],
            [Filters::FILTER_BOOL, 'false', false],
            [Filters::FILTER_BOOL, 'no', false],
            [Filters::FILTER_BOOL, 'off', false],
            [Filters::FILTER_BOOL, 'foo bar', null],
            [Filters::FILTER_BOOL, null, null],
            [Filters::FILTER_BOOL, '', null],
            [Filters::FILTER_BOOL, [], null],
            [Filters::FILTER_BOOL, new \ArrayObject(), null],
            [Filters::FILTER_INT, 1, 1],
            [Filters::FILTER_INT, 0777, 511],
            [Filters::FILTER_INT, '123', 123],
            [Filters::FILTER_INT, '123.123', 123],
            [Filters::FILTER_INT, 123.456, 123],
            [Filters::FILTER_INT, true, 1],
            [Filters::FILTER_INT, false, 0],
            [Filters::FILTER_INT, 'foo', null],
            [Filters::FILTER_INT, [], null],
            [Filters::FILTER_INT, new \ArrayObject(), null],
            [Filters::FILTER_STRING, 'hello!', 'hello!'],
            [Filters::FILTER_STRING, '<script>alert(\'hi!\')</script>', 'alert(&#39;hi!&#39;)'],
            [Filters::FILTER_STRING, 1, '1'],
            [Filters::FILTER_STRING, 123.456, '123.456'],
            [Filters::FILTER_STRING, 0, '0'],
            [Filters::FILTER_STRING, new \ArrayObject(), null],
            [Filters::FILTER_STRING, false, ''],
            [Filters::FILTER_STRING, true, '1'],
            [Filters::FILTER_STRING, [], null],
            [Filters::FILTER_INT_OR_BOOL, 1, 1],
            [Filters::FILTER_INT_OR_BOOL, '1', 1],
            [Filters::FILTER_INT_OR_BOOL, 123.123, 123],
            [Filters::FILTER_INT_OR_BOOL, 0777, 511],
            [Filters::FILTER_INT_OR_BOOL, '123.123', 123],
            [Filters::FILTER_INT_OR_BOOL, 'true', true],
            [Filters::FILTER_INT_OR_BOOL, 'yes', true],
            [Filters::FILTER_INT_OR_BOOL, 'on', true],
            [Filters::FILTER_INT_OR_BOOL, 'false', false],
            [Filters::FILTER_INT_OR_BOOL, 'no', false],
            [Filters::FILTER_INT_OR_BOOL, 'off', false],
            [Filters::FILTER_INT_OR_BOOL, 'foo bar', null],
            [Filters::FILTER_INT_OR_BOOL, null, null],
            [Filters::FILTER_INT_OR_BOOL, '', null],
            [Filters::FILTER_INT_OR_BOOL, [], null],
            [Filters::FILTER_INT_OR_BOOL, new \ArrayObject(), null],
            [Filters::FILTER_OCTAL_MOD, 0444, 0444],
            [Filters::FILTER_OCTAL_MOD, 0666, 0666],
            [Filters::FILTER_OCTAL_MOD, 0, 0],
            [Filters::FILTER_OCTAL_MOD, '0744', 0744],
            [Filters::FILTER_OCTAL_MOD, '744', 0744],
            [Filters::FILTER_OCTAL_MOD, '74', 074],
            [Filters::FILTER_OCTAL_MOD, 511, 0777],
            [Filters::FILTER_OCTAL_MOD, 999, null],
            [Filters::FILTER_OCTAL_MOD, 'foo', null],
            [Filters::FILTER_OCTAL_MOD, true, null],
            [Filters::FILTER_OCTAL_MOD, false, null],
            [Filters::FILTER_OCTAL_MOD, new \ArrayObject(), null],
            ['foo', new \ArrayObject(), null],
            ['bar', true, null],
            ['baz', [], null],
            ['boolean', true, null],
        ];
    }
}
