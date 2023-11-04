<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Io;

use Composer\IO\IOInterface;
use WeCodeMore\WpStarter\Io\Formatter;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Io\Question;
use WeCodeMore\WpStarter\Tests\TestCase;

class IoTest extends TestCase
{
    /**
     * @test
     */
    public function testAskTooMuchTries(): void
    {
        $question = new Question(['Yes or no?'], ['y' => 'Yes', 'n' => 'No']);

        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo
            ->expects('ask')
            ->times(5)
            ->andReturn('a', 'b', 'c', 'd', 'e');
        $composerIo
            ->expects('write')
            ->times(4)
            ->with(\Mockery::type('string'))
            ->andReturnUsing(
                static function (string $error): void {
                    static::assertStringContainsString('Invalid answer, try again', $error);
                }
            );
        $composerIo
            ->expects('writeError')
            ->twice()
            ->with(\Mockery::type('string'))
            ->andReturnUsing(
                static function (string $error): void {
                    static $i;
                    $i = $i ?? 0;
                    $i++;
                    $i === 1 and static::assertStringContainsString('Too much tries', $error);
                    $i === 2 and static::assertStringContainsString('Going to use default', $error);
                }
            );

        $io = new Io($composerIo, new Formatter());

        $answer = $io->ask($question);

        static::assertSame('y', $answer);
    }

    /**
     * @test
     */
    public function testAskTooMuchTriesWithValidator(): void
    {
        $question = Question::newWithValidator(
            ['URL?'],
            static function (string $value): bool {
                return (bool)filter_var($value, FILTER_VALIDATE_URL);
            },
            'https://example.com'
        );

        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo
            ->expects('ask')
            ->times(5)
            ->andReturn('ht', 'meh', '12', 'a', 'e');
        $composerIo
            ->expects('write')
            ->times(4)
            ->with(\Mockery::type('string'))
            ->andReturnUsing(
                static function (string $error): void {
                    static::assertStringContainsString('Invalid answer, try again', $error);
                }
            );
        $composerIo
            ->expects('writeError')
            ->twice()
            ->with(\Mockery::type('string'))
            ->andReturnUsing(
                static function (string $error): void {
                    static $i;
                    $i = $i ?? 0;
                    $i++;
                    $i === 1 and static::assertStringContainsString('Too much tries', $error);
                    $i === 2 and static::assertStringContainsString('Going to use default', $error);
                }
            );

        $io = new Io($composerIo, new Formatter());

        $answer = $io->ask($question);

        static::assertSame('https://example.com', $answer);
    }

    /**
     * @test
     */
    public function testAskWithRightAnswer(): void
    {
        $question = new Question(['Yes or no?'], ['y' => 'Yes', 'n' => 'No']);

        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo->expects('ask')->once()->andReturn(' Y ');
        $composerIo->expects('write')->never();
        $composerIo->expects('writeError')->never();

        $io = new Io($composerIo, new Formatter());

        $answer = $io->ask($question);

        static::assertSame('y', $answer);
    }

    /**
     * @test
     */
    public function testAskWithValidatorWithRightAnswer(): void
    {
        $question = Question::newWithValidator(
            ['URL?'],
            static function (string $value): bool {
                return (bool)filter_var($value, FILTER_VALIDATE_URL);
            },
            'https://example.com'
        );

        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo->expects('ask')->once()->andReturn(' https://wikipedia.org ');
        $composerIo->expects('write')->never();
        $composerIo->expects('writeError')->never();

        $io = new Io($composerIo, new Formatter());

        $answer = $io->ask($question);

        static::assertSame('https://wikipedia.org', $answer);
    }

    /**
     * @test
     */
    public function testAskConfirmDefaultFalseAnswerYes(): void
    {
        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo
            ->expects('ask')
            ->once()
            ->andReturnUsing(
                static function (string $message): string {
                    static::assertStringContainsString('Yes or No?', $message);
                    static::assertStringContainsString('[Y]es | [N]o', $message);
                    static::assertStringContainsString("Default: 'n'", $message);

                    return 'y';
                }
            );

        $io = new Io($composerIo, new Formatter());

        static::assertTrue($io->askConfirm(['Yes or No?'], false));
    }

    /**
     * @test
     */
    public function testAskConfirmDefaultFalseAnswerNo(): void
    {
        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo
            ->expects('ask')
            ->once()
            ->andReturnUsing(
                static function (string $message): string {
                    static::assertStringContainsString('Yes or No?', $message);
                    static::assertStringContainsString('[Y]es | [N]o', $message);
                    static::assertStringContainsString("Default: 'n'", $message);

                    return 'N';
                }
            );

        $io = new Io($composerIo, new Formatter());

        static::assertFalse($io->askConfirm(['Yes or No?'], false));
    }

    /**
     * @test
     */
    public function testAskConfirmDefaultTrueAnswerYes(): void
    {
        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo
            ->expects('ask')
            ->once()
            ->andReturnUsing(
                static function (string $message): string {
                    static::assertStringContainsString('Yes or No?', $message);
                    static::assertStringContainsString('[Y]es | [N]o', $message);
                    static::assertStringContainsString("Default: 'y'", $message);

                    return ' Y';
                }
            );

        $io = new Io($composerIo, new Formatter());

        static::assertTrue($io->askConfirm(['Yes or No?'], true));
    }

    /**
     * @test
     */
    public function testAskConfirmDefaultTrueAnswerNo(): void
    {
        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo
            ->expects('ask')
            ->once()
            ->andReturnUsing(
                static function (string $message): string {
                    static::assertStringContainsString('Yes or No?', $message);
                    static::assertStringContainsString('[Y]es | [N]o', $message);
                    static::assertStringContainsString("Default: 'y'", $message);

                    return ' n';
                }
            );

        $io = new Io($composerIo, new Formatter());

        static::assertFalse($io->askConfirm(['Yes or No?'], true));
    }
}
