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
     * @return bool
     */
    public function isValidAnswer(string $answer): bool
    {
        return array_key_exists(strtolower(trim($answer)), $this->answers);
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
        return ($this->default !== '') ? $this->answers[$this->default] : '';
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

        if (($this->lines === []) || ($this->answers === [])) {
            $this->question = [];

            return [];
        }

        $this->question = $this->lines;
        array_unshift($this->question, 'QUESTION:');
        $this->question[] = "";
        $this->question[] = implode(' | ', $this->answers);
        ($this->default !== '') and $this->question[] = "Default: '{$this->default}'";

        return $this->question;
    }
}
