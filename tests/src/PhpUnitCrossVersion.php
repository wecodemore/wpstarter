<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests;

trait PhpUnitCrossVersion
{
    /**
     * @param string $msgRegex
     * @return void
     */
    public function expectExceptionMsgRegex(string $msgRegex)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        method_exists($this, 'expectExceptionMessageMatches')
            ? $this->expectExceptionMessageMatches($msgRegex)
            : $this->expectExceptionMessageRegExp($msgRegex);
    }

    /**
     * @param string $regex
     * @param string $subject
     * @return void
     */
    public static function assertStringMatchesRegex(string $regex, string $subject)
    {
        method_exists(parent::class, 'assertMatchesRegularExpression')
            ? static::assertMatchesRegularExpression($regex, $subject)
            : static::assertRegExp($regex, $subject);
    }
}
