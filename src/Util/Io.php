<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\IO\IOInterface;

/**
 * Wrapper around Composer IO class.
 */
class Io
{
    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * Print an error line.
     *
     * @param string $message
     * @return bool
     */
    public function error(string $message): bool
    {
        $tag = 'bg=red;fg=white;option=bold>';
        $lines = $this->ensureLength($message);
        $this->io->write('');
        foreach ($lines as $line) {
            $this->io->writeError("  <{$tag}  " . $line . "  </{$tag}");
        }
        $this->io->write('');

        return false;
    }

    /**
     * @param  string $message
     * @return bool
     */
    public function ok(string $message): bool
    {
        $lines = $this->ensureLength($message);
        foreach ($lines as $i => $line) {
            $prefix = $i === 0 ? '  - <info>[OK]</info> ' : '         ';
            $this->io->write($prefix . $line);
        }

        return true;
    }

    /**
     * @param  string $message
     * @return bool
     */
    public function comment(string $message): bool
    {
        $lines = $this->ensureLength($message);
        foreach ($lines as $line) {
            $this->io->write("  <comment>{$line}</comment>");
        }

        return true;
    }

    /**
     * Get an array of question lines and a default response and use them to format and ask a
     * confirmation to console.
     *
     * @param  array $lines
     * @param  bool $default
     * @return bool
     */
    public function confirm(array $lines, bool $default = true): bool
    {
        array_unshift($lines, 'QUESTION');

        $length = max(array_map('strlen', $lines));

        array_walk(
            $lines,
            function (string &$line) use ($length) {
                $len = strlen($line);
                if ($len < $length) {
                    $line .= str_repeat(' ', $length - $len);
                }
                $line = "  {$line}  ";
            }
        );

        $space = str_repeat(' ', $length + 4);
        array_unshift($lines, '  <question>' . $space);
        $lines[] = "{$space}</question>";
        $question = PHP_EOL . implode('</question>' . PHP_EOL . '  <question>', $lines);
        $prompt = PHP_EOL . '    <option=bold>Y</option=bold> or <option=bold>N</option=bold> ';
        $prompt .= $default ? '[Y]' : '[N]';

        return $this->io->askConfirmation($question . PHP_EOL . $prompt, $default);
    }

    /**
     * Get an array of question lines and a default response and use them to format and ask a
     * question to console.
     *
     * @param array $lines
     * @param array $answers
     * @param string|bool $default
     * @return mixed
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    public function ask(array $lines, array $answers = [], $default = null)
    {
        // phpcs:enable

        $answers = array_change_key_case($answers, CASE_LOWER);
        $answers = array_map('trim', array_filter($answers, 'is_string'));
        is_string($default) && array_key_exists($default, $answers) or $default = null;

        array_unshift($lines, 'QUESTION');
        $length = max(array_map('strlen', $lines));
        array_walk(
            $lines,
            function (string &$line) use ($length) {
                $len = strlen($line);
                $len < $length and $line .= str_repeat(' ', $length - $len);
                $line = "  {$line}  ";
            }
        );

        $space = str_repeat(' ', $length + 4);
        array_unshift($lines, '  <question>' . $space);
        $lines[] = "{$space}</question>";
        $question = PHP_EOL . implode('</question>' . PHP_EOL . '  <question>', $lines);
        $prompt = '';
        foreach ($answers as $expected => $label) {
            $prompt and $prompt .= '|';
            $prompt .= '<option=bold>' . $label . '</option=bold>';
        }

        $default and $prompt .= "[{$answers[$default]}]";
        $question .= PHP_EOL . PHP_EOL . '    ' . $prompt;

        try {
            $answer = $this->io->ask($question, $default);
            while (!array_key_exists(strtolower(trim($answer)), $answers)) {
                $this->io->write('<comment>Invalid answer.</comment>');
                $answer = $this->io->ask($question, $default);
            }

            return $answer;
        } catch (\Throwable $exception) {
            if ($default === null) {
                reset($answers);

                return key($answers);
            }

            return $default;
        }
    }

    /**
     * Print to console a block of text using an array of lines.
     *
     * @param  array $lines
     * @param  string $background
     * @param  bool $isError
     * @return bool
     */
    public function block(array $lines, string $background = 'green', bool $isError = false): bool
    {
        $length = max(array_map('strlen', $lines));
        array_walk(
            $lines,
            function (string &$line) use ($length) {
                $len = strlen($line);
                ($len < $length) and $line .= str_repeat(' ', $length - $len);
                $line = "  {$line}  ";
            }
        );

        $front = $isError ? 'white;option=bold' : 'black';
        $space = str_repeat(' ', $length + 4);
        $open = "  <bg={$background};fg={$front}>";
        $close = "  </bg={$background};fg={$front}>";
        array_unshift($lines, $open . $space);
        $lines[] = $space . $close;
        $func = $isError ? 'writeError' : 'write';
        $this->io->{$func}(PHP_EOL . implode($close . PHP_EOL . $open, $lines));

        return !$isError;
    }

    /**
     * @param string $line
     */
    public function write(string $line)
    {
        $this->io->write("  {$line}");
    }

    /**
     * @param string $line
     */
    public function writeIfVerbose(string $line)
    {
        $this->io->write("  {$line}", IOInterface::VERBOSE);
    }

    /**
     * @return \Composer\IO\IOInterface
     */
    public function composerIo(): IOInterface
    {
        return clone $this->io;
    }

    /**
     * Return an array where each item is a slice of the given string with less than 70 chars.
     *
     * @param  string $text
     * @return array
     */
    private function ensureLength(string $text): array
    {
        if (strlen($text) < 70) {
            return [$text];
        }

        $words = explode(' ', $text);
        $line = '';
        foreach ($words as $i => $word) {
            if (strlen($line) + strlen($word) < 70) {
                $line .= "{$word} ";
                continue;
            }

            $lines[] = trim($line);
            $line = "{$word} ";
        }

        $lines[] = trim($line);

        return $lines;
    }
}
