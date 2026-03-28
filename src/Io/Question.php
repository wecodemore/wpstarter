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
    /** @var list<string> */
    private array $lines;

    /** @var array<string, string> */
    private array $answers = [];

    private string $default = '';

    /** @var list<string>|null */
    private ?array $question = null;

    /** @var callable|null */
    private $validator = null; // phpcs:ignore

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
     */
    public function __construct(array $lines, array $answers = [], ?string $default = null)
    {
        $this->lines = [];
        foreach ($lines as $line) {
            $trimLine = trim($line);
            if ($trimLine !== '') {
                $this->lines[] = $trimLine;
            }
        }

        $defaultKey = null;
        foreach ($answers as $key => $value) {
            $key = trim($key);
            $value = trim($value);
            if (($key !== '') && ($value !== '')) {
                $defaultKey ??= $key;
                $this->answers[strtolower($key)] = $value;
            }
        }

        if ($this->answers === []) {
            return;
        }

        if ($default !== null) {
            $default = strtolower(trim($default));
            array_key_exists($default, $this->answers) or $default = null;
        }

        $this->default = ($default ?? $defaultKey ?? '');
    }

    /**
     * @param string $answer
     * @return string|null
     */
    public function filterAnswer(string $answer): ?string
    {
        $answer = trim($answer);

        if ($this->validator !== null) {
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
        return ($this->default !== '' && $this->answers !== [])
            ? ($this->answers[$this->default] ?? '')
            : $this->defaultAnswerKey();
    }

    /**
     * @return list<string>
     *
     * @phpstan-assert list<string> $this->question
     */
    public function questionLines(): array
    {
        if (is_array($this->question)) {
            return $this->question;
        }

        if ($this->lines === []) {
            $this->question = [];

            return [];
        }

        $this->question = $this->lines;
        array_unshift($this->question, 'QUESTION:');
        if ($this->answers === [] && $this->default === '') {
            return $this->question;
        }

        $this->question[] = "";
        $this->answers !== [] and $this->question[] = implode(' | ', $this->answers);
        $this->default !== '' and $this->question[] = "Default: '{$this->default}'";

        return $this->question;
    }
}
