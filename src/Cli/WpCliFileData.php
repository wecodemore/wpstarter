<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Cli;

/**
 * @phpstan-type ParsedData = array{
 *     file: string,
 *     args: list<non-empty-string>,
 *     skip-wordpress:bool,
 *     valid:bool
 * }
 */
final class WpCliFileData
{
    public const FILE = 'file';
    public const ARGS = 'args';
    public const SKIP_WORDPRESS = 'skip-wordpress';
    public const VALID = 'valid';
    public const DEFAULTS = [
        self::FILE => '',
        self::ARGS => [],
        self::SKIP_WORDPRESS => false,
        self::VALID => false,
    ];

    /** @var array<mixed> */
    private array $raw;

    /** @var ParsedData|null */
    private ?array $parsed = null;

    /**
     * @param array<mixed> $fileData
     * @return WpCliFileData
     */
    public static function fromArray(array $fileData): WpCliFileData
    {
        return new static($fileData);
    }

    /**
     * @param string $path
     * @return WpCliFileData
     */
    public static function fromPath(string $path): WpCliFileData
    {
        return new static([self::FILE => $path]);
    }

    /**
     * @param array<mixed> $fileData
     */
    private function __construct(array $fileData)
    {
        $this->raw = $fileData;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        $this->parse();

        return $this->parsed[self::VALID];
    }

    /**
     * @return string
     */
    public function file(): string
    {
        $this->parse();

        return $this->parsed[self::FILE];
    }

    /**
     * @return bool
     */
    public function skipWordpress(): bool
    {
        $this->parse();

        return $this->parsed[self::SKIP_WORDPRESS];
    }

    /**
     * @return list<non-empty-string>
     */
    public function args(): array
    {
        $this->parse();

        return $this->parsed[self::ARGS];
    }

    /**
     * @return void
     *
     * @phpstan-assert ParsedData $this->parsed
     */
    private function parse(): void
    {
        if ($this->parsed !== null) {
            return;
        }

        $data = array_replace(self::DEFAULTS, $this->raw);

        $file = $data[self::FILE] ?? null;
        if (($file === '') || !is_string($file) || !is_file($file)) {
            $this->parsed = self::DEFAULTS;

            return;
        }
        /** @var non-falsy-string $file */

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'php') {
            $this->parsed = self::DEFAULTS;

            return;
        }

        $rawArgs = $data[self::ARGS] ?? [];
        $args = [];
        if (is_array($rawArgs)) {
            foreach ($rawArgs as $rawArg) {
                (is_string($rawArg) && ($rawArg !== '')) and $args[] = $rawArg;
            }
        }

        $skip = filter_var($data[self::SKIP_WORDPRESS], FILTER_VALIDATE_BOOLEAN);

        $this->parsed = [
            self::FILE => $file,
            self::ARGS => $args,
            self::SKIP_WORDPRESS => $skip,
            self::VALID => true,
        ];
    }
}
