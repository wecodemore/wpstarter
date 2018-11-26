<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Cli;

final class WpCliFileData
{
    const FILE = 'file';
    const ARGS = 'args';
    const SKIP_WORDPRESS = 'skip-wordpress';
    const VALID = 'valid';
    const DEFAULTS = [
        self::FILE => '',
        self::ARGS => [],
        self::SKIP_WORDPRESS => false,
        self::VALID => false,
    ];

    /**
     * @var array
     */
    private $raw;

    /**
     * @var array
     */
    private $parsed = self::DEFAULTS;

    /**
     * @param array $fileData
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
     * @param array $fileData
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
        $this->setup();

        return $this->parsed[self::VALID];
    }

    /**
     * @return string
     */
    public function file(): string
    {
        $this->setup();

        return $this->parsed[self::FILE];
    }

    /**
     * @return bool
     */
    public function skipWordpress(): bool
    {
        $this->setup();

        return $this->parsed[self::SKIP_WORDPRESS];
    }

    /**
     * @return array
     */
    public function args(): array
    {
        $this->setup();

        return $this->parsed[self::ARGS];
    }

    /**
     * @return void
     */
    private function setup()
    {
        if (is_array($this->parsed)) {
            return;
        }

        $data = array_replace(self::DEFAULTS, $this->raw);

        /** @var string|null $file */
        $file = $data[self::FILE] ?? null;
        if (!$file || !is_string($file) || !is_file($file)) {
            $this->parsed = self::DEFAULTS;

            return;
        }

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'php') {
            $this->parsed = self::DEFAULTS;

            return;
        }

        /** @var array $baseArgs */
        $baseArgs = $data[self::ARGS] ?? [];
        $args = is_array($baseArgs) ? array_filter($baseArgs, 'is_string') : [];
        $skip = (bool)filter_var($data[self::SKIP_WORDPRESS], FILTER_VALIDATE_BOOLEAN);

        $this->parsed = [
            self::FILE => $data[self::FILE],
            self::ARGS => array_filter($args),
            self::SKIP_WORDPRESS => $skip,
            self::VALID => true,
        ];
    }
}
