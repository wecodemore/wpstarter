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
 * Wrapper around Composer IO with helper for WP Starter specific input and output.
 */
class Io
{
    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * Return an array where each item is a slice of the given string with less than 51 characters.
     *
     * @param string[] $lines
     * @return array
     *
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    public static function ensureLength(string ...$lines): array
    {
        if (!$lines) {
            return [];
        }

        $parsed = [];
        $lines = preg_split('~\n+~', implode("\n", $lines));

        foreach ($lines as $line) {
            $words = preg_split('~\s+~', trim($line));
            $buffer = '';
            foreach ($words as $word) {
                if (!$word) {
                    continue;
                }
                if (!trim(strip_tags($word))) {
                    $buffer .= $word;
                    continue;
                }
                if (strlen(strip_tags($buffer . $word)) > 51) {
                    $parsed[] = trim($buffer);
                    $buffer = "{$word} ";
                    continue;
                }

                $buffer .= "{$word} ";
            }

            $parsed[] = trim($buffer);
        }

        // phpcs:enable

        return array_filter($parsed);
    }

    /**
     * @param string $before
     * @param string $after
     * @param string[] $lines
     * @return array
     */
    private static function createBlock(
        string $before = '',
        string $after = '',
        string ...$lines
    ): array {

        if (!$lines) {
            return [];
        }

        $lines = static::ensureLength(...$lines);
        $topLength = max(array_map('strlen', array_map('trim', array_map('strip_tags', $lines))));
        $length = 56;
        $leftSpace = (int)floor(($length - $topLength) / 2);

        $whiteLine = $before . str_repeat(' ', 60) . $after;
        $block = ['', $whiteLine];
        foreach ($lines as $line) {
            $len = strlen(strip_tags($line));
            if ($len < $length) {
                $rightSpace = (56 - $leftSpace) - $len;
                $line = str_repeat(' ', $leftSpace) . trim($line) . str_repeat(' ', $rightSpace);
            }
            $block[] = "{$before}  {$line}  {$after}";
        }
        $block[] = $whiteLine;

        return $block;
    }

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
        return $this->writeBlock('red', true, ...static::ensureLength($message));
    }

    /**
     * @param  string $message
     * @return bool
     */
    public function writeSuccess(string $message): bool
    {
        $lines = static::ensureLength($message);
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
        $lines = static::ensureLength($message);
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
        $this->io->write("  {$line}", true, IOInterface::VERBOSE);
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
        $lines = static::createBlock('<question>', '</question>', ...$lines);

        $question = implode("\n", $lines);
        $question .= "\n<question>  <option=bold>Y</option=bold> or <option=bold>N</option=bold>  ";
        $question .= $default ? '[Y]' : '[N]';
        $question .= '</question>';

        return $this->io->askConfirmation($question, $default);
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
        $lines = static::createBlock('<question>', '</question>', ...$lines);

        $question = implode("\n", $lines) . "\n\n";

        $prompt = '';
        foreach ($answers as $expected => $label) {
            $prompt and $prompt .= ' | ';
            $prompt .= '<question><option=bold>' . $label . '</option=bold>';
        }

        $default and $prompt .= " [{$answers[$default]}]";
        $question .= "{$prompt}</question>";

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
     * @param string[] $lines
     * @return bool
     */
    public function writeErrorBlock(string ...$lines): bool
    {
        return $this->writeBlock('red', true, ...$lines);
    }

    /**
     * @param string[] $lines
     * @return bool
     */
    public function writeSuccessBlock(string ...$lines): bool
    {
        return $this->writeBlock('green', false, ...$lines);
    }

    /**
     * @param string[] $lines
     * @return bool
     */
    public function writeYellowBlock(string ...$lines): bool
    {
        return $this->writeBlock('yellow', false, ...$lines);
    }

    /**
     * Print to console a block of text using an array of lines.
     *
     * @param  string $background
     * @param  bool $isError
     * @param  string[] $lines
     * @return bool
     */
    private function writeBlock(
        string $background,
        bool $isError,
        string ...$lines
    ): bool {

        $frontground = $isError ? 'white;option=bold' : 'black';

        $block = self::createBlock("<bg={$background};fg={$frontground}>  ", '  </>', ...$lines);

        $isError ? $this->io->writeError($block) : $this->io->write($block);

        return !$isError;
    }
}
