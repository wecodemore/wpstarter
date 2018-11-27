<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Io;

class Formatter
{
    const DEFAULT_LINE_LENGTH = 58;

    /**
     * @param string ...$lines
     * @return array
     */
    public function ensureLinesLength(string ...$lines): array
    {
        if (!$lines) {
            return [];
        }

        $lines = $this->splitLinesByLineEnding(...$lines);
        $normalized = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!$trimmed) {
                $normalized[] = $line;
                continue;
            }

            $normalizedByLength = $this->normalizeLength($trimmed);
            foreach ($normalizedByLength as $normalizedLine) {
                $normalized[] = $normalizedLine;
            }
        }

        return $normalized;
    }

    /**
     * @param string $before
     * @param string $after
     * @param string ...$lines
     * @return array
     */
    public function createCenteredBlock(
        string $before = '',
        string $after = '',
        string ...$lines
    ): array {

        return $this->createBlock($before, $after, true, ...$lines);
    }

    /**
     * @param string $before
     * @param string $after
     * @param string ...$lines
     * @return array
     */
    public function createFilledBlock(
        string $before = '',
        string $after = '',
        string ...$lines
    ): array {

        return $this->createBlock($before, $after, false, ...$lines);
    }

    /**
     * @param string $text
     * @param int $length
     * @return string[]
     */
    private function normalizeLength(string $text, int $length = self::DEFAULT_LINE_LENGTH): array
    {
        $words = preg_split('~\s+~', $text);
        $buffer = '';
        $normalized = [];
        foreach ($words as $word) {
            if (!$word) {
                continue;
            }
            if (!trim(strip_tags($word))) {
                $buffer .= $word;
                continue;
            }
            if (strlen(strip_tags($buffer . $word)) > ($length - 7)) {
                $normalized[] = trim($buffer);
                $buffer = "{$word} ";
                continue;
            }

            $buffer .= "{$word} ";
        }

        $leftOver = trim($buffer);
        $leftOver and $normalized[] = $leftOver;

        return $normalized;
    }

    /**
     * @param string $before
     * @param string $after
     * @param bool $centered
     * @param string ...$lines
     * @return array
     */
    private function createBlock(
        string $before = '',
        string $after = '',
        bool $centered = false,
        string ...$lines
    ): array {

        if (!$lines) {
            return [];
        }

        $before = rtrim($before);
        $after = ltrim($after);

        $lines = $this->ensureLinesLength(...$lines);

        $count = count($lines);
        $firstLineIsEmpty = !$this->trimLine($lines[0]);
        $lastLineIsEmpty = $count > 1 && !$this->trimLine($lines[$count - 1]);
        $firstLineIsEmpty and array_shift($lines);
        $lastLineIsEmpty and array_pop($lines);

        if (!$lines) {
            return [];
        }

        $maxLength = $this->calculateMaxLineLength($before, $after, ...$lines);
        $whiteLine = str_repeat(' ', $maxLength);
        $spaces = $centered
            ? $this->calculateSpacesToCenterLines($maxLength, ...$lines)
            : $this->calculateSpacesToFillLines($maxLength, ...$lines);

        if (!$lines) {
            return [];
        }

        array_unshift($lines, '');
        array_unshift($spaces, ['', '']);
        $lines[] = '';
        $spaces[] = ['', ''];

        $block = [''];
        foreach ($lines as $i => $line) {
            $filled = $line ? $spaces[$i][0] . $line . $spaces[$i][1] : $whiteLine;
            $block[] = "{$before}  {$filled}  {$after}";
        }
        $block[] = '';

        return $block;
    }

    /**
     * @param string ...$lines
     * @return string[]
     */
    private function splitLinesByLineEnding(string ...$lines): array
    {
        $split = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!$trimmed) {
                $split[] = '';
            }

            $innerLines = preg_split('~\n+~', $trimmed);
            foreach ($innerLines as $innerLine) {
                $trimmedInner = trim($innerLine);
                $trimmedInner and $split[] = $trimmedInner;
            }
        }

        return $split;
    }

    /**
     * @param string $line
     * @return string
     */
    private function trimLine(string $line): string
    {
        return strip_tags(trim($line));
    }

    /**
     * @param string $before
     * @param string $after
     * @param string ...$lines
     * @return int
     */
    private function calculateMaxLineLength(string $before, string $after, string ...$lines): int
    {
        $linesLength = array_map('strlen', array_map([$this, 'trimLine'], $lines));
        $linesLength[] = self::DEFAULT_LINE_LENGTH;

        $maxLength = max($linesLength) - strlen(strip_tags($before . $after));

        return max($maxLength, 2);
    }

    /**
     * @param int $maxLength
     * @param string ...$lines
     * @return array{0:string,1:string}[]
     */
    private function calculateSpacesToCenterLines(int $maxLength, string ...$lines): array
    {
        $halfSpace = $maxLength / 2;
        $leftSpaceForEmpty = str_repeat(' ', (int)floor($halfSpace));
        $rightSpaceForEmpty = str_repeat(' ', (int)ceil($halfSpace));

        $spacesMap = [];
        foreach ($lines as $i => $line) {
            $trimmed = $this->trimLine($line);
            if (!$trimmed) {
                $spacesMap[$i] = [$leftSpaceForEmpty, $rightSpaceForEmpty];
                continue;
            }

            $missingSpaceLength = $maxLength - strlen($trimmed);
            if ($missingSpaceLength < 2) {
                $spacesMap[$i] = [' ', ' '];
                continue;
            }

            $missingSpaceLengthHalf = $missingSpaceLength / 2;

            $spacesMap[$i] = [
                str_repeat(' ', (int)floor($missingSpaceLengthHalf)),
                str_repeat(' ', (int)ceil($missingSpaceLengthHalf)),
            ];
        }

        return $spacesMap;
    }

    /**
     * @param int $maxLength
     * @param string ...$lines
     * @return array{0:string,1:string}[]
     */
    private function calculateSpacesToFillLines(int $maxLength, string ...$lines): array
    {
        $baseSpace = str_repeat(' ', $maxLength);

        $spacesMap = [];
        foreach ($lines as $i => $line) {
            $trimmed = $this->trimLine($line);
            if (!$trimmed) {
                $spacesMap[$i] = ['', $baseSpace];
                continue;
            }

            $missingSpaceLength = ($maxLength - strlen($trimmed));
            if (!$missingSpaceLength) {
                $spacesMap[$i] = ['', ''];
                continue;
            }

            $spacesMap[$i] = [
                '',
                str_repeat(' ', $missingSpaceLength),
            ];
        }

        return $spacesMap;
    }
}
