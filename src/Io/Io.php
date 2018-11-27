<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Io;

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
     * @var Formatter
     */
    private $formatter;

    /**
     * @param \Composer\IO\IOInterface $io
     * @param Formatter|null $formatter
     */
    public function __construct(IOInterface $io, Formatter $formatter = null)
    {
        $this->io = $io;
        $this->formatter = $formatter ?: new Formatter();
    }

    /**
     * @param  string $message
     * @return bool
     */
    public function writeSuccess(string $message): bool
    {
        $lines = $this->formatter->ensureLinesLength($message);
        foreach ($lines as $i => $line) {
            $prefix = $i === 0 ? '  - <info>[OK]</info> ' : '         ';
            $this->io->write($prefix . $line);
        }

        return true;
    }

    /**
     * @param string ...$lines
     * @return bool
     */
    public function writeSuccessBlock(string ...$lines): bool
    {
        return $this->writeColorCenteredBlock('green', ...$lines);
    }

    /**
     * @param  string $message
     * @return bool
     */
    public function writeComment(string $message): bool
    {
        $lines = $this->formatter->ensureLinesLength($message);
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
        $lines = $this->formatter->ensureLinesLength($line);
        foreach ($lines as $line) {
            $this->io->write("  <comment>{$line}</comment>", true, IOInterface::VERBOSE);
        }

        return true;
    }

    /**
     * @param string $line
     */
    public function writeError(string $line)
    {
        $lines = $this->formatter->ensureLinesLength($line);
        foreach ($lines as $line) {
            $this->io->writeError("  <fg=red>{$line}</>");
        }
    }

    /**
     * @param string $line
     */
    public function writeErrorIfVerbose(string $line)
    {
        $this->io->writeError("  <fg=red>{$line}</>", true, IOInterface::VERBOSE);
    }

    /**
     * @param string ...$lines
     * @return bool
     */
    public function writeErrorBlock(string ...$lines): bool
    {
        return $this->writeColorBlock('red', ...$lines);
    }

    /**
     * @param string $line
     */
    public function write(string $line)
    {
        $lines = $this->formatter->ensureLinesLength($line);
        foreach ($lines as $line) {
            $this->io->write("  {$line}");
        }
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
        $question = new Question($lines, ['y' => '[Y]es', 'n' => '[N]o'], $default ? 'y' : 'n');
        $answer = $this->ask($question);
        $answer or $answer = $question->defaultAnswer();

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

        try {
            $answer = null;
            $count = 0;
            while (!is_string($answer) || !$question->isValidAnswer((string)$answer)) {
                if ($count > 4) {
                    usleep(250000);
                    throw new \Exception('Too much tries.');
                }
                if ($count > 0) {
                    $this->writeComment('Invalid answer, try again.');
                    usleep(250000);
                }
                $answer = $this->io->ask($questionText, $question->defaultAnswer());
                $answer = is_string($answer) ? strtolower(trim($answer)) : null;
                $count++;
            }

            return $answer === null ? null : (string)$answer;
        } catch (\Throwable $exception) {
            $default = $question->defaultAnswerValue();
            $this->writeError($exception->getMessage());
            $this->writeError("Going to use default: \"{$default}\".");

            return $default;
        }
    }

    /**
     * @param string $color
     * @param string ...$lines
     * @return bool
     */
    public function writeColorBlock(string $color, string ...$lines): bool
    {
        return $this->writeBlock($color, strtolower($color) === 'red', ...$lines);
    }

    /**
     * @param string $color
     * @param string ...$lines
     * @return bool
     */
    public function writeColorCenteredBlock(string $color, string ...$lines): bool
    {
        return $this->writeCenteredBlock($color, strtolower($color) === 'red', ...$lines);
    }

    /**
     * Print to console a block of text using an array of lines.
     *
     * @param  string $background
     * @param  bool $isError
     * @param  string ...$lines
     * @return bool
     */
    private function writeBlock(
        string $background,
        bool $isError,
        string ...$lines
    ): bool {

        $frontground = $isError ? 'white;options=bold' : 'black';

        $block = $this->formatter->createFilledBlock(
            "<bg={$background};fg={$frontground}>",
            '</>',
            ...$lines
        );

        $isError ? $this->io->writeError($block) : $this->io->write($block);

        return !$isError;
    }

    /**
     * Print to console a block of text using an array of lines.
     *
     * @param  string $background
     * @param  bool $isError
     * @param  string ...$lines
     * @return bool
     */
    private function writeCenteredBlock(
        string $background,
        bool $isError,
        string ...$lines
    ): bool {

        $frontground = $isError ? 'white;options=bold' : 'black';

        $block = $this->formatter->createCenteredBlock(
            "<bg={$background};fg={$frontground}>",
            '</>',
            ...$lines
        );

        $isError ? $this->io->writeError($block) : $this->io->write($block);

        return !$isError;
    }

    /**
     * @return bool
     */
    public function isVerbose(): bool
    {
        return $this->io->isVerbose();
    }
}
