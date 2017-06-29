<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Utils;

use Composer\IO\IOInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
class IO
{

    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * @var int
     */
    private $verbosity;

    /**
     * Constructor.
     *
     * @param \Composer\IO\IOInterface $io
     * @param int $verbosity
     */
    public function __construct(IOInterface $io, $verbosity = 2)
    {
        $this->io = $io;
        $this->verbosity = $verbosity;
    }

    /**
     * Print an error line.
     *
     * @param  string $message
     * @return bool   Always false
     */
    public function error($message)
    {
        if ($message && $this->verbosity > 0) {
            $tag = 'bg=red;fg=white;option=bold>';
            $lines = $this->ensureLength($message);
            $this->io->write('');
            foreach ($lines as $line) {
                $this->io->writeError("  <{$tag}  " . $line . "  </{$tag}");
            }
            $this->io->write('');
        }

        return false;
    }

    /**
     * Print an ok line.
     *
     * @param  string $message
     * @return bool   Always true
     */
    public function ok($message)
    {
        if ($message && $this->verbosity > 0) {
            $lines = $this->ensureLength($message);
            foreach ($lines as $i => $line) {
                if ($i === 0) {
                    $this->io->write('  - <info>[OK]</info> ' . $line);
                } else {
                    $this->io->write('         ' . $line);
                }
            }
        }

        return true;
    }

    /**
     * Print a comment line.
     *
     * @param  string $message
     * @return bool   Always true
     */
    public function comment($message)
    {
        if ($message && $this->verbosity > 0) {
            $lines = $this->ensureLength($message);
            foreach ($lines as $line) {
                $this->io->write('  <comment>' . $line . '</comment>');
            }
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
    public function confirm(array $lines, $default = true)
    {
        if ($this->verbosity < 1) {
            return $default;
        }
        array_unshift($lines, 'QUESTION');
        $length = max(array_map('strlen', $lines));
        array_walk($lines, function (&$line) use ($length) {
            $len = strlen($line);
            if ($len < $length) {
                $line .= str_repeat(' ', $length - $len);
            }
            $line = "  {$line}  ";
        });
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
     * @return bool
     */
    public function ask(array $lines, array $answers = [], $default = null)
    {
        $answers = array_change_key_case($answers, CASE_LOWER);
        $answers = array_map('trim', array_filter($answers, 'is_string'));
        is_string($default) && array_key_exists($default, $answers) or $default = null;

        if ($this->verbosity < 1) {
            return $default ?: key($answers);
        }

        array_unshift($lines, 'QUESTION');
        $length = max(array_map('strlen', $lines));
        array_walk($lines, function (&$line) use ($length) {
            $len = strlen($line);
            if ($len < $length) {
                $line .= str_repeat(' ', $length - $len);
            }
            $line = "  {$line}  ";
        });
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
        } catch (\RuntimeException $exception) {
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
     * @param  bool $is_error
     * @return bool
     */
    public function block(array $lines, $background = 'green', $is_error = false)
    {
        $minSeverity = $background === 'green' ? 1 : 0;

        if ($this->verbosity < $minSeverity) {
            return !$is_error;
        }

        $length = max(array_map('strlen', $lines));
        array_walk($lines, function (&$line) use ($length) {
            $len = strlen($line);
            if ($len < $length) {
                $line .= str_repeat(' ', $length - $len);
            }
            $line = "  {$line}  ";
        });
        $fg = $is_error ? 'white;option=bold' : 'black';
        $space = str_repeat(' ', $length + 4);
        $open = "  <bg={$background};fg={$fg}>";
        $close = "  </bg={$background};fg={$fg}>";
        array_unshift($lines, $open . $space);
        $lines[] = $space . $close;
        $func = $is_error ? 'writeError' : 'write';
        call_user_func([$this->io, $func], PHP_EOL . implode($close . PHP_EOL . $open, $lines));

        return !$is_error;
    }

    /**
     * @param string $line
     */
    public function write($line)
    {
        $this->io->write('  ' . $line);
    }

    /**
     * @param string $line
     */
    public function writeVerbose($line)
    {
        $this->verbosity > 1 and $this->io->write('  ' . $line);
    }

    /**
     * @return \Composer\IO\IOInterface
     */
    public function composerIo()
    {
        return clone $this->io;
    }

    /**
     * Return an array where each item is a slice of the given string with less than 70 chars.
     *
     * @param  string $text
     * @return array
     */
    private function ensureLength($text)
    {
        if (strlen($text) < 70) {
            return [$text];
        }
        $words = explode(' ', $text);
        $line = '';
        foreach ($words as $i => $word) {
            if (strlen($line) + strlen($word) < 70) {
                $line .= $word . ' ';
            } else {
                $lines[] = trim($line);
                $line = $word . ' ';
            }
        }
        $lines[] = trim($line);

        return $lines;
    }
}
