<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Io;

use Composer\IO\IOInterface;
use WeCodeMore\WpStarter\Io\Formatter;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Io\Question;
use WeCodeMore\WpStarter\Tests\TestCase;

class IoTest extends TestCase
{
    public function testAskTooMuchTries()
    {
        $question = new Question(['Yes or no?'], ['y' => 'Yes', 'n' => 'No']);

        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo
            ->shouldReceive('ask')
            ->times(5)
            ->andReturn('a', 'b', 'c', 'd', 'e');
        $composerIo
            ->shouldReceive('write')
            ->times(4)
            ->with(\Mockery::type('string'))
            ->andReturnUsing(
                function (string $error) {
                    static::assertContains('Invalid answer, try again', $error);
                }
            );
        $composerIo
            ->shouldReceive('writeError')
            ->twice()
            ->with(\Mockery::type('string'))
            ->andReturnUsing(
                function (string $error) {
                    static $i;
                    $i = $i ?? 0;
                    $i++;
                    $i === 1 and static::assertContains('Too much tries', $error);
                    $i === 2 and static::assertContains('Going to use default', $error);
                }
            );

        $io = new Io($composerIo, new Formatter());

        $answer = $io->ask($question);

        static::assertSame('y', $answer);
    }

    public function testAskWithRightAnswer()
    {
        $question = new Question(['Yes or no?'], ['y' => 'Yes', 'n' => 'No']);

        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo->shouldReceive('ask')->once()->andReturn(' Y ');
        $composerIo->shouldReceive('write')->never();
        $composerIo->shouldReceive('writeError')->never();

        $io = new Io($composerIo, new Formatter());

        $answer = $io->ask($question);

        static::assertSame('y', $answer);
    }

    public function testAskConfirmDefaultFalseAnswerYes()
    {
        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo
            ->shouldReceive('ask')
            ->once()
            ->andReturnUsing(
                function (string $message): string {
                    static::assertContains('Yes or No?', $message);
                    static::assertContains('[Y]es | [N]o', $message);
                    static::assertContains("Default: 'n'", $message);

                    return 'y';
                }
            );

        $io = new Io($composerIo, new Formatter());

        static::assertTrue($io->askConfirm(['Yes or No?'], false));
    }

    public function testAskConfirmDefaultFalseAnswerNo()
    {
        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo
            ->shouldReceive('ask')
            ->once()
            ->andReturnUsing(
                function (string $message): string {
                    static::assertContains('Yes or No?', $message);
                    static::assertContains('[Y]es | [N]o', $message);
                    static::assertContains("Default: 'n'", $message);

                    return 'N';
                }
            );

        $io = new Io($composerIo, new Formatter());

        static::assertFalse($io->askConfirm(['Yes or No?'], false));
    }

    public function testAskConfirmDefaultTrueAnswerYes()
    {
        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo
            ->shouldReceive('ask')
            ->once()
            ->andReturnUsing(
                function (string $message): string {
                    static::assertContains('Yes or No?', $message);
                    static::assertContains('[Y]es | [N]o', $message);
                    static::assertContains("Default: 'y'", $message);

                    return ' Y';
                }
            );

        $io = new Io($composerIo, new Formatter());

        static::assertTrue($io->askConfirm(['Yes or No?'], true));
    }

    public function testAskConfirmDefaultTrueAnswerNo()
    {
        $composerIo = \Mockery::mock(IOInterface::class);
        $composerIo
            ->shouldReceive('ask')
            ->once()
            ->andReturnUsing(
                function (string $message): string {
                    static::assertContains('Yes or No?', $message);
                    static::assertContains('[Y]es | [N]o', $message);
                    static::assertContains("Default: 'y'", $message);

                    return ' n';
                }
            );

        $io = new Io($composerIo, new Formatter());

        static::assertFalse($io->askConfirm(['Yes or No?'], true));
    }
}
