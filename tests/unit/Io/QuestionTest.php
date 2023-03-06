<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Io;

use WeCodeMore\WpStarter\Io\Question;
use WeCodeMore\WpStarter\Tests\TestCase;

class QuestionTest extends TestCase
{
    /**
     * @test
     */
    public function testQuestionInstance(): void
    {
        $question = new Question(['This is a question'], ['y' => 'Yes', 'n' => 'No'], 'n');

        static::assertSame($question->defaultAnswerKey(), 'n');
        static::assertSame($question->defaultAnswerText(), 'No');
        static::assertSame('y', $question->filterAnswer('y'));
        static::assertSame('y', $question->filterAnswer('Y'));
        static::assertSame('y', $question->filterAnswer(' Y '));
        static::assertSame('y', $question->filterAnswer(' y '));
        static::assertSame('n', $question->filterAnswer('n'));
        static::assertSame('n', $question->filterAnswer('N'));
        static::assertSame('n', $question->filterAnswer(' N '));
        static::assertSame('n', $question->filterAnswer(' n'));
        static::assertNull($question->filterAnswer('x'));
    }

    /**
     * @test
     */
    public function testQuestionInstanceWithWongDefault(): void
    {
        $question = new Question(['This is a question'], ['y' => 'Yes', 'n' => 'No'], 'x');

        static::assertSame($question->defaultAnswerKey(), 'y');
        static::assertSame($question->defaultAnswerText(), 'Yes');
    }

    /**
     * @test
     */
    public function testQuestionLinesNoDefault(): void
    {
        $question = new Question(['This is a question'], ['y' => 'Yes', 'n' => 'No']);

        $expected = [
            'QUESTION:',
            'This is a question',
            '',
            'Yes | No',
            "Default: 'y'",
        ];

        static::assertSame($expected, $question->questionLines());
    }

    /**
     * @test
     */
    public function testQuestionLinesWithDefault(): void
    {
        $question = new Question(['This is a question'], ['y' => 'Yes', 'n' => 'No'], 'n');

        $expected = [
            'QUESTION:',
            'This is a question',
            '',
            'Yes | No',
            "Default: 'n'",
        ];

        static::assertSame($expected, $question->questionLines());
    }

    /**
     * @test
     */
    public function testQuestionLinesWithValidator(): void
    {
        $question = Question::newWithValidator(
            ['Give me a valid URL'],
            static function (string $value): bool {
                return (bool)filter_var($value, FILTER_VALIDATE_URL);
            },
            'https://example.org'
        );

        $expected = [
            'QUESTION:',
            'Give me a valid URL',
            '',
            "Default: 'https://example.org'",
        ];

        static::assertSame($expected, $question->questionLines());
        static::assertSame('https://example.org', $question->defaultAnswerText());
        static::assertSame('https://example.org', $question->defaultAnswerKey());
    }

    /**
     * @test
     */
    public function testQuestionLinesWithValidatorWithWrongDefault(): void
    {
        $question = Question::newWithValidator(
            ['Give me a valid URL'],
            static function (string $value): bool {
                return (bool)filter_var($value, FILTER_VALIDATE_URL);
            },
            'meh'
        );

        $expected = [
            'QUESTION:',
            'Give me a valid URL',
        ];

        static::assertSame($expected, $question->questionLines());
        static::assertSame('', $question->defaultAnswerText());
        static::assertSame('', $question->defaultAnswerKey());
    }
}
