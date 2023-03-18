<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Io;

class Formatter
{
    public const DEFAULT_LINE_LENGTH = 58;

    /**
     * @param int $lineLength
     * @param string ...$lines
     * @return list<string>
     */
    public function ensureLinesLength(int $lineLength, string ...$lines): array
    {
        if (!$lines) {
            return [];
        }

        $lines = $this->splitLinesByLineEnding(...$lines);
        $normalized = [];

        foreach ($lines as $line) {
            if (!$this->trimLine($line)) {
                $normalized[] = $line;
                continue;
            }

            $normalizedByLength = $this->normalizeLength(trim($line), $lineLength);
            foreach ($normalizedByLength as $normalizedLine) {
                $normalized[] = $normalizedLine;
            }
        }

        return $normalized;
    }

    /**
     * @param string ...$lines
     * @return array<string>
     */
    public function ensureDefaultLinesLength(string ...$lines): array
    {
        return $this->ensureLinesLength(self::DEFAULT_LINE_LENGTH, ...$lines);
    }

    /**
     * @param string $before
     * @param string $after
     * @param string ...$lines
     * @return list<string>
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
     * @return list<string>
     */
    public function createFilledBlock(
        string $before = '',
        string $after = '',
        string ...$lines
    ): array {

        return $this->createBlock($before, $after, false, ...$lines);
    }

    /**
     * @param string ...$items
     * @return string[]
     */
    public function createList(string ...$items): array
    {
        return $this->createListWithPrefix(' -', ...$items);
    }

    /**
     * @param string $prefix
     * @param string ...$items
     * @return string[]
     */
    public function createListWithPrefix(string $prefix, string ...$items): array
    {
        $prefix = rtrim($prefix) . ' ';
        $prefixLen = strlen(strip_tags($prefix));
        $filler = str_repeat(' ', $prefixLen);

        $list = [];
        foreach ($items as $item) {
            if (!$item) {
                continue;
            }

            $length = self::DEFAULT_LINE_LENGTH - (strlen($this->trimLine($prefix)) + 1);
            $innerLines = $this->ensureLinesLength($length, $item);
            $list[] = $prefix . (string)array_shift($innerLines);
            foreach ($innerLines as $innerLine) {
                $innerLine and $list[] = $filler . $innerLine;
            }
        }

        return $list;
    }

    /**
     * @param string $text
     * @param int $length
     * @return array<string>
     */
    private function normalizeLength(string $text, int $length = self::DEFAULT_LINE_LENGTH): array
    {
        /** @var array<string> $words */
        $words = (array)(preg_split('~\s+~', $text) ?: []);
        $buffer = '';
        $normalized = [];
        foreach ($words as $word) {
            if (!$word) {
                continue;
            }
            if (!$this->trimLine($word)) {
                $buffer .= $word;
                continue;
            }
            if (strlen(strip_tags($buffer . $word)) > ($length - 4)) {
                $trimmedBuffer = trim($buffer);
                $trimmedBuffer and $normalized[] = $trimmedBuffer;
                $buffer = $word;
                continue;
            }

            $buffer .= " {$word}";
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
     * @return list<string>
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

        $lines = $this->ensureDefaultLinesLength(...$lines);

        // Will later ensure empty lines on top and on bottom, so remove if they're already there.
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

        // Ensure empty line on top and on bottom of block
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
     * @return array<string>
     */
    private function splitLinesByLineEnding(string ...$lines): array
    {
        $split = [];
        foreach ($lines as $line) {
            if (!$this->trimLine($line)) {
                $split[] = '';
            }

            /** @var array<string> $innerLines */
            $innerLines = (array)(preg_split('~\n+~', trim($line)) ?: []);
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

            $missingSpaceLength = $maxLength - strlen($trimmed);
            if ($missingSpaceLength < 1) {
                $spacesMap[$i] = ['', ''];
                continue;
            }

            $spacesMap[$i] = ['', str_repeat(' ', $missingSpaceLength)];
        }

        return $spacesMap;
    }
}
