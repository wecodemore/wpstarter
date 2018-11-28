<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Io;

use WeCodeMore\WpStarter\Io\Question;
use WeCodeMore\WpStarter\Tests\TestCase;

class QuestionTest extends TestCase
{
    public function testQuestionInstance()
    {
        $question = new Question(['This is a question'], ['y' => 'Yes', 'n' => 'No'], 'n');

        self::assertSame($question->defaultAnswerKey(), 'n');
        self::assertSame($question->defaultAnswerText(), 'No');
        self::assertTrue($question->isValidAnswer('y'));
        self::assertTrue($question->isValidAnswer('Y'));
        self::assertTrue($question->isValidAnswer(' Y '));
        self::assertTrue($question->isValidAnswer(' y '));
        self::assertTrue($question->isValidAnswer('n'));
        self::assertTrue($question->isValidAnswer('N'));
        self::assertTrue($question->isValidAnswer(' N '));
        self::assertTrue($question->isValidAnswer(' n'));
        self::assertFalse($question->isValidAnswer('x'));
    }

    public function testQuestionInstanceWithWongDefault()
    {
        $question = new Question(['This is a question'], ['y' => 'Yes', 'n' => 'No'], 'x');

        self::assertSame($question->defaultAnswerKey(), 'y');
        self::assertSame($question->defaultAnswerText(), 'Yes');
    }

    public function testQuestionLinesNoDefault()
    {
        $question = new Question(['This is a question'], ['y' => 'Yes', 'n' => 'No']);

        $expected = [
            'QUESTION:',
            'This is a question',
            '',
            'Yes | No',
            "Default: 'y'",
        ];

        self::assertSame($expected, $question->questionLines());
    }

    public function testQuestionLinesWithDefault()
    {
        $question = new Question(['This is a question'], ['y' => 'Yes', 'n' => 'No'], 'n');

        $expected = [
            'QUESTION:',
            'This is a question',
            '',
            'Yes | No',
            "Default: 'n'",
        ];

        self::assertSame($expected, $question->questionLines());
    }
}
