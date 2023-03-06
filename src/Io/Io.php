<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Io;

use Composer\IO\IOInterface;

/**
 * Wrapper around Composer IO with helper for WP Starter specific input and output.
 */
class Io
{
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @param IOInterface $io
     * @param Formatter|null $formatter
     */
    public function __construct(IOInterface $io, ?Formatter $formatter = null)
    {
        $this->io = $io;
        $this->formatter = $formatter ?: new Formatter();
    }

    /**
     * @param  string $message
     * @return void
     */
    public function writeSuccess(string $message): void
    {
        $lines = $this->formatter->createListWithPrefix('  - <info>[OK]</info>', $message);
        array_map([$this->io, 'write'], $lines);
    }

    /**
     * @param string ...$lines
     * @return void
     */
    public function writeSuccessBlock(string ...$lines): void
    {
        $this->writeCenteredColorBlock('green', 'black', ...$lines);
    }

    /**
     * @param  string $message
     * @return bool
     */
    public function writeComment(string $message): bool
    {
        $lines = $this->formatter->ensureDefaultLinesLength($message);
        foreach ($lines as $line) {
            $this->io->write("  <comment>{$line}</comment>");
        }

        return true;
    }

    /**
     * @param string $line
     * @return bool
     */
    public function writeCommentIfVerbose(string $line): bool
    {
        $lines = $this->formatter->ensureDefaultLinesLength($line);
        foreach ($lines as $line) {
            $this->io->write("  <comment>{$line}</comment>", true, IOInterface::VERBOSE);
        }

        return true;
    }

    /**
     * @param string ...$lines
     * @return void
     */
    public function writeCommentBlock(string ...$lines): void
    {
        $this->writeFilledColorBlock('yellow', 'black', ...$lines);
    }

    /**
     * @param string $line
     * @return void
     */
    public function writeError(string $line): void
    {
        $lines = $this->formatter->ensureDefaultLinesLength($line);
        foreach ($lines as $line) {
            $this->io->writeError("  <fg=red>{$line}</>");
        }
    }

    /**
     * @param string $line
     * @return void
     */
    public function writeErrorIfVerbose(string $line): void
    {
        $this->io->writeError("  <fg=red>{$line}</>", true, IOInterface::VERBOSE);
    }

    /**
     * @param string ...$lines
     * @return void
     */
    public function writeErrorBlock(string ...$lines): void
    {
        $this->writeFilledErrorColorBlock('red', 'white', ...$lines);
    }

    /**
     * @param string $line
     * @return void
     */
    public function write(string $line): void
    {
        $lines = $this->formatter->ensureDefaultLinesLength($line);
        foreach ($lines as $line) {
            $this->io->write("  {$line}");
        }
    }

    /**
     * @param string $line
     * @return void
     */
    public function writeIfVerbose(string $line): void
    {
        $this->io->write("  {$line}", true, IOInterface::VERBOSE);
    }

    /**
     * Get an array of question lines and a default response and use them to format and ask a
     * confirmation to console.
     *
     * @param array<string> $lines
     * @param  bool $default
     * @return bool
     */
    public function askConfirm(array $lines, bool $default = true): bool
    {
        $question = new Question($lines, ['y' => '[Y]es', 'n' => '[N]o'], $default ? 'y' : 'n');
        $answer = $this->ask($question);
        $answer or $answer = $question->defaultAnswerKey();

        return $answer === 'y';
    }

    /**
     * Get an array of question lines and a default response and use them to format and ask a
     * question to console.
     *
     * @param Question $question
     * @return string|null
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    public function ask(Question $question)
    {
        $lines = $question->questionLines();
        if (!$lines) {
            return null;
        }

        $block = $this->formatter->createFilledBlock('<question>', '</question>', ...$lines);
        $questionText = implode("\n", $block);

        $tooMuchTriesException = new \Exception('Too much tries.');

        try {
            $answer = null;
            $count = 0;
            while ($answer === null) {
                if ($count > 4) {
                    usleep(250000);
                    throw $tooMuchTriesException;
                }
                if ($count > 0) {
                    $this->writeComment('Invalid answer, try again.');
                    usleep(250000);
                }
                $asked = $this->io->ask($questionText, $question->defaultAnswerKey());
                $answer = is_string($asked) ? $question->filterAnswer($asked) : null;
                $count++;
            }

            return $answer;
        } catch (\Exception $exception) {
            if ($exception !== $tooMuchTriesException) {
                throw $exception;
            }

            $default = $question->defaultAnswerKey();
            if ($default) {
                $this->writeError($exception->getMessage());
                $defaultText = $question->defaultAnswerText();
                $this->writeError("Going to use default: \"{$defaultText}\".");
            }

            return $default;
        }
    }

    /**
     * @param string $background
     * @param string $frontground
     * @param string ...$lines
     * @return void
     */
    public function writeFilledColorBlock(
        string $background,
        string $frontground = 'black',
        string ...$lines
    ): void {

        $this->writeColorBlock($background, $frontground, false, false, ...$lines);
    }

    /**
     * @param string $background
     * @param string $frontground
     * @param string ...$lines
     * @return void
     */
    public function writeCenteredColorBlock(
        string $background,
        string $frontground = 'black',
        string ...$lines
    ): void {

        $this->writeColorBlock($background, $frontground, true, false, ...$lines);
    }

    /**
     * @param string $background
     * @param string $frontground
     * @param string ...$lines
     * @return void
     */
    public function writeFilledErrorColorBlock(
        string $background,
        string $frontground = 'black',
        string ...$lines
    ): void {

        $this->writeColorBlock($background, $frontground, false, true, ...$lines);
    }

    /**
     * @param string $background
     * @param string $frontground
     * @param string ...$lines
     * @return void
     */
    public function writeCenteredErrorColorBlock(
        string $background,
        string $frontground = 'black',
        string ...$lines
    ): void {

        $this->writeColorBlock($background, $frontground, true, true, ...$lines);
    }

    /**
     * @return bool
     */
    public function isVerbose(): bool
    {
        return $this->io->isVerbose();
    }

    /**
     * @param string $background
     * @param string $frontground
     * @param bool $centered
     * @param bool $isError
     * @param string ...$lines
     * @return bool
     */
    private function writeColorBlock(
        string $background,
        string $frontground,
        bool $centered,
        bool $isError,
        string ...$lines
    ): bool {

        $before = "<bg={$background};fg={$frontground}>";

        $block = $centered
            ? $this->formatter->createCenteredBlock($before, '</>', ...$lines)
            : $this->formatter->createFilledBlock($before, '</>', ...$lines);

        $isError ? $this->io->writeError($block) : $this->io->write($block);

        return !$isError;
    }
}
