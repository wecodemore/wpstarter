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
 * Wrapper around Composer IO.
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
    public function writeError(string $message): bool
    {
        return $this->writeBlock($this->ensureLength($message), 'red', true);
    }

    /**
     * @param  string $message
     * @return bool
     */
    public function writeSuccess(string $message): bool
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
    public function writeComment(string $message): bool
    {
        $lines = $this->ensureLength($message);
        foreach ($lines as $line) {
            $this->io->write("  <comment>{$line}</comment>");
        }

        return true;
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
     * Get an array of question lines and a default response and use them to format and ask a
     * confirmation to console.
     *
     * @param  array $lines
     * @param  bool $default
     * @return bool
     */
    public function askConfirm(array $lines, bool $default = true): bool
    {
        array_unshift($lines, 'QUESTION:');

        $length = max(array_map('strlen', $lines));

        array_walk(
            $lines,
            function (string &$line) use ($length) {
                $len = strlen($line);
                ($len < $length) and $line .= str_repeat(' ', $length - $len);
                $line = "  {$line}  ";
            }
        );

        $space = str_repeat(' ', $length + 4);
        array_unshift($lines, "  <question>{$space}");
        $lines[] = "{$space}</question>";
        $question = "\n" . implode("</question>\n  <question>", $lines);
        $prompt = "\n    <option=bold>Y</option=bold> or <option=bold>N</option=bold> ";
        $prompt .= $default ? '[Y]' : '[N]';

        return $this->io->askConfirmation("{$question}\n{$prompt}", $default);
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
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    public function ask(array $lines, array $answers = [], $default = null)
    {
        // phpcs:enable

        $answers = array_change_key_case($answers, CASE_LOWER);
        $answers = array_map('trim', array_filter($answers, 'is_string'));
        is_string($default) && array_key_exists($default, $answers) or $default = null;

        array_unshift($lines, 'QUESTION');

        $length = max(array_map('strlen', $lines));
        $length < 56 and $length = 56;

        $block = [];
        foreach ($lines as $line) {
            $len = strlen($line);
            ($len < $length) and $line .= str_repeat(' ', $length - $len);
            $block[] = "<question>  {$line}  </question>";
        }

        $whiteLine = '<question>' . str_repeat(' ', $length + 4) . '</question>';
        $question = "{$whiteLine}\n";
        $question .= implode("\n", $block);
        $question .= "\n{$whiteLine}\n{$whiteLine}\n{$whiteLine}";

        $prompt = '';
        foreach ($answers as $expected => $label) {
            $prompt and $prompt .= ' | ';
            $prompt .= '<option=bold>' . $label . '</option=bold>';
        }

        $default and $prompt .= " [{$answers[$default]}]";
        $question .= "\n{$prompt}";

        try {
            $answer = $this->io->ask($question, $default);
            $count = 0;
            while (!array_key_exists(strtolower(trim($answer)), $answers)) {
                if ($count > 5) {
                    throw new \Exception('Too much tries.');
                }
                $this->write('<comment>Invalid answer.</comment>');
                $answer = $this->io->ask($question, $default);
                $count++;
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
    public function writeBlock(
        array $lines,
        string $background = 'green',
        bool $isError = false
    ): bool {

        $length = max(array_map('strlen', $lines));

        $frontground = $isError ? 'white;option=bold' : 'black';
        $open = "  <bg={$background};fg={$frontground}>";
        $close = "  </bg={$background};fg={$frontground}>";
        $whiteLine = $open . str_repeat(' ', $length + 4) . $close;

        $block = ['', $whiteLine];
        foreach ($lines as $line) {
            $len = strlen($line);
            ($len < $length) and $line .= str_repeat(' ', $length - $len);
            $block[] = "{$open}  {$line}  {$close}";
        }
        $block[] = $whiteLine;
        $block[] = '';

        $isError ? $this->io->writeError($block) : $this->io->write($block);

        return !$isError;
    }

    /**
     * Return an array where each item is a slice of the given string with less than 51 characters.
     *
     * @param  string $text
     * @return array
     */
    public function ensureLength(string $text): array
    {
        if (strlen($text) < 51) {
            return [$text];
        }

        $words = explode(' ', $text);
        $line = '';
        foreach ($words as $i => $word) {
            if (strlen($line . $word) < 51) {
                $line .= "{$word} ";
                continue;
            }

            $lines[] = trim($line);
            $line = "{$word} ";
        }

        $lines[] = trim($line);

        return array_filter($lines);
    }
}
