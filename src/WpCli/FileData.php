<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\WpCli;

final class FileData
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
    private $parsed;

    /**
     * @param array $fileData
     * @return FileData
     */
    public static function fromArray(array $fileData): FileData
    {
        return new static($fileData);
    }

    /**
     * @param string $path
     * @return FileData
     */
    public static function fromPath(string $path): FileData
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
     * @return string
     */
    public function valid()
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

        if (!$data[self::FILE] || !is_string($data[self::FILE]) || !is_file($data[self::FILE])) {
            $this->parsed = self::DEFAULTS;

            return;
        }

        $ext = (string)pathinfo($data[self::FILE], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'php') {
            $this->parsed = self::DEFAULTS;

            return;
        }

        $args = is_array($data[self::ARGS]) ? array_filter($data[self::ARGS], 'is_string') : [];
        $skip = filter_var($data[self::SKIP_WORDPRESS], FILTER_VALIDATE_BOOLEAN);

        $this->parsed = [
            self::FILE => $data[self::FILE],
            self::ARGS => array_filter($args),
            self::SKIP_WORDPRESS => (bool)$skip,
            self::VALID => true,
        ];
    }
}
