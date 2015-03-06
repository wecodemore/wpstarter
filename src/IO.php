<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter;

use Composer\IO\IOInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class IO
{
    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * Constructor.
     *
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * Print an error line.
     *
     * @param  string $message
     * @return bool   Always false
     */
    public function error($message)
    {
        $tag = 'bg=red;fg=white;option=bold>';
        $lines = $this->ensureLength($message);
        $this->io->write('');
        foreach ($lines as $line) {
            $this->io->writeError("  <{$tag}  ".$line."  </{$tag}");
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
        $lines = $this->ensureLength($message);
        foreach ($lines as $i => $line) {
            if ($i === 0) {
                $this->io->write('  - <info>OK</info> '.$line);
            } else {
                $this->io->write('   '.$line);
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
        $lines = $this->ensureLength($message);
        foreach ($lines as $line) {
            $this->io->write('  <comment>'.$line.'</comment>');
        }

        return true;
    }

    /**
     * Get an array of question lines and a default response and use them to format and ask a
     * question to console.
     *
     * @param  array $lines
     * @param  bool  $default
     * @return bool
     */
    public function ask(array $lines, $default = true)
    {
        array_unshift($lines, 'QUESTION');
        $length = max(array_map('strlen', $lines));
        array_walk($lines, function (&$line) use ($length) {
            $len = strlen($line);
            if ($len < $length) {
                $line = $line.str_repeat(' ', $length - $len);
            }
            $line = "  {$line}  ";
        });
        $space = str_repeat(' ', $length + 4);
        array_unshift($lines, '  <question>'.$space);
        array_push($lines, $space.'</question>');
        $question = PHP_EOL.implode('</question>'.PHP_EOL.'  <question>', $lines);
        $prompt = PHP_EOL.'    <option=bold>Y</option=bold> or <option=bold>N</option=bold> ';
        $prompt .= $default ? '[Y]' : '[N]';

        return $this->io->askConfirmation($question.PHP_EOL.$prompt, $default);
    }

    /**
     * Print to console a block of text using an array of lines.
     *
     * @param  array  $lines
     * @param  string $background
     * @param  bool   $is_error
     * @return bool
     */
    public function block(array $lines, $background = 'green', $is_error = false)
    {
        $length = max(array_map('strlen', $lines));
        array_walk($lines, function (&$line) use ($length) {
            $len = strlen($line);
            if ($len < $length) {
                $line = $line.str_repeat(' ', $length - $len);
            }
            $line = "  {$line}  ";
        });
        $fg = $is_error ? 'white;option=bold' : 'black';
        $space = str_repeat(' ', $length + 4);
        $open = "  <bg={$background};fg={$fg}>";
        $close = "  </bg={$background};fg={$fg}>";
        array_unshift($lines, $open.$space);
        array_push($lines, $space.$close);
        $func = $is_error ? 'writeError' : 'write';
        call_user_func(array($this->io, $func), PHP_EOL.implode($close.PHP_EOL.$open, $lines));

        return ! $is_error;
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
            return array($text);
        }
        $words = explode(' ', $text);
        $line = '';
        foreach ($words as $i => $word) {
            if (strlen($line) + strlen($word) < 70) {
                $line .= $word.' ';
            } else {
                $lines[] = trim($line);
                $line = $word.' ';
            }
        }
        $lines[] = trim($line);

        return $lines;
    }
}
