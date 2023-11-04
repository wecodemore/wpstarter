<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Io;

class Question
{
    /**
     * @var array<string>
     */
    private $lines;

    /**
     * @var array<string, string>
     */
    private $answers = [];

    /**
     * @var string
     */
    private $default = '';

    /**
     * @var array<string>|null
     */
    private $question;

    /**
     * @var callable|null
     */
    private $validator = null;

    /**
     * @param array<string> $lines
     * @param callable(mixed):bool $validator
     * @param string|null $default
     * @return Question
     */
    public static function newWithValidator(
        array $lines,
        callable $validator,
        ?string $default = null
    ): Question {

        $instance = new self($lines, [], null);
        $instance->validator = $validator;
        if ($default !== null && $validator($default)) {
            $instance->default = $default;
        }

        return $instance;
    }

    /**
     * @param array<string> $lines
     * @param array<string, string> $answers
     * @param string|null $default
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.IncorrectVoidReturn
     */
    public function __construct(array $lines, array $answers = [], ?string $default = null)
    {
        // phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration.IncorrectVoidReturn
        $this->lines = array_filter(
            $lines,
            static function (string $line): bool {
                return (bool)trim($line);
            }
        );

        if (!$this->lines) {
            return;
        }

        $validAnswers = array_filter(
            $answers,
            static function (string $value, string $key): bool {
                return trim($value) && trim($key);
            },
            ARRAY_FILTER_USE_BOTH
        );

        if (!$validAnswers) {
            return;
        }

        $validAnswers = array_change_key_case($validAnswers, CASE_LOWER);
        $answerKeys = array_map('trim', array_keys($validAnswers));

        $this->answers = array_combine($answerKeys, array_values($validAnswers)) ?: [];

        if ($default !== null) {
            $default = strtolower(trim($default));
            array_key_exists($default, $this->answers) or $default = null;
        }

        $this->default = $default ?? $answerKeys[0];
    }

    /**
     * @param string $answer
     * @return string|null
     */
    public function filterAnswer(string $answer): ?string
    {
        $answer = trim($answer);

        if ($this->validator) {
            return ($this->validator)($answer) ? $answer : null;
        }

        $answer = strtolower($answer);

        return array_key_exists($answer, $this->answers) ? $answer : null;
    }

    /**
     * @return string
     */
    public function defaultAnswerKey(): string
    {
        return $this->default;
    }

    /**
     * @return string
     */
    public function defaultAnswerText(): string
    {
        return ($this->default && $this->answers)
            ? ($this->answers[$this->default] ?? '')
            : $this->defaultAnswerKey();
    }

    /**
     * @return array<string>
     *
     * @psalm-assert array<string> $this->question
     */
    public function questionLines(): array
    {
        if (is_array($this->question)) {
            return $this->question;
        }

        if (!$this->lines) {
            $this->question = [];

            return [];
        }

        $this->question = array_values($this->lines);
        array_unshift($this->question, 'QUESTION:');
        if (!$this->answers && !$this->default) {
            return $this->question;
        }

        $this->question[] = "";
        $this->answers and $this->question[] = implode(' | ', $this->answers);
        $this->default and $this->question[] = "Default: '{$this->default}'";

        return $this->question;
    }
}
