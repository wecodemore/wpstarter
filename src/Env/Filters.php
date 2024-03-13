<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Env;

/**
 * Filters to set assign proper types to env variables.
 *
 * Env variables are always strings. WP Starter uses env variables to store WordPress configuration
 * constants, but those might need to be set with different types. E.g. an env var "false" needs to
 * be set as _boolean_ `false`, even because PHP evaluates "false" string as true.
 *
 * This class provides a way to filter variable and assign proper type. This class is not aware of
 * the "conversion strategy" to use, that is passed beside the input value to class methods.
 * The strategy to use is defined in the `WordPressEnvBridge` class constants.
 *
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
 * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
 */
final class Filters
{
    public const FILTER_BOOL = 'bool';
    public const FILTER_INT = 'int';
    public const FILTER_FLOAT = 'float';
    public const FILTER_INT_OR_BOOL = 'int|bool';
    public const FILTER_STRING_OR_BOOL = 'string|bool';
    public const FILTER_STRING = 'string';
    public const FILTER_RAW_STRING = 'raw-string';
    public const FILTER_OCTAL_MOD = 'mod';
    public const FILTER_TABLE_PREFIX = 'table-prefix';

    /**
     * @param string $name
     * @return string
     */
    public static function resolveFilterName(string $name): string
    {
        $cleanName = $name ? trim($name) : '';
        if (!$cleanName) {
            return '';
        }

        $constant = 'FILTER_' . strtoupper($cleanName);
        if ($constant !== 'FILTER_TABLE_PREFIX' && defined(__CLASS__ . "::{$constant}")) {
            return (string)constant(__CLASS__ . "::{$constant}");
        }

        return '';
    }

    /**
     * Return given value filtered based on "mode".
     *
     * @param string $mode One of the `FILTER_*` class constants.
     * @param mixed $value
     * @return int|float|bool|string|null
     */
    public function filter(string $mode, $value)
    {
        try {
            return $this->applyFilter($mode, $value);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * @param string $mode
     * @param mixed $value
     * @return int|float|bool|string|null
     */
    private function applyFilter(string $mode, $value)
    {
        switch ($mode) {
            case self::FILTER_BOOL:
                return $this->filterBool($value);
            case self::FILTER_INT:
                return $this->filterInt($value);
            case self::FILTER_FLOAT:
                return $this->filterFloat($value);
            case self::FILTER_STRING:
                return $this->filterString($value);
            case self::FILTER_RAW_STRING:
                return $this->filterRawString($value);
            case self::FILTER_INT_OR_BOOL:
                return $this->filterIntOrBool($value);
            case self::FILTER_STRING_OR_BOOL:
                return $this->filterStringOrBool($value);
            case self::FILTER_OCTAL_MOD:
                return $this->filterOctalMod($value);
            case self::FILTER_TABLE_PREFIX:
                return $this->filterTablePrefix($value);
        }

        return null;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function filterBool($value): bool
    {
        if (in_array($value, ['', null], true)) {
            throw new \Exception('Invalid bool.');
        }

        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($bool === null) {
            throw new \Exception('Invalid bool.');
        }

        return $bool;
    }

    /**
     * @param mixed $value
     * @return int
     */
    private function filterInt($value): int
    {
        if (!is_numeric($value) && !is_bool($value)) {
            throw new \Exception('Invalid integer.');
        }

        return (int)$value;
    }

    /**
     * @param mixed $value
     * @return float
     */
    private function filterFloat($value): float
    {
        if (!is_numeric($value)) {
            throw new \Exception('Invalid float.');
        }

        return (float)$value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function filterString($value): string
    {
        if (!is_scalar($value)) {
            throw new \Exception('Invalid scalar.');
        }

        return htmlspecialchars(strip_tags((string)$value), ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function filterRawString($value): string
    {
        if (!is_scalar($value)) {
            throw new \Exception('Invalid scalar.');
        }

        return addslashes((string)$value);
    }

    /**
     * @param mixed $value
     * @return bool|int
     */
    private function filterIntOrBool($value)
    {
        return is_numeric($value) ? $this->filterInt($value) : $this->filterBool($value);
    }

    /**
     * @param mixed $value
     * @return bool|string
     */
    private function filterStringOrBool($value)
    {
        $var = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $var === null ? $this->filterString($value) : $this->filterBool($value);
    }

    /**
     * @param mixed $value
     * @return int
     */
    private function filterOctalMod($value): int
    {
        if (is_int($value) && ($value >= 0) && ($value <= 0777)) {
            /** @var int $value */
            return $value;
        }

        if (!is_string($value) || !is_numeric($value)) {
            throw new \Exception('Invalid octal mod.');
        }

        return (int)octdec($value);
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function filterTablePrefix($value): string
    {
        if (!$value || !is_string($value)) {
            return 'wp_';
        }

        return (string)preg_replace('#\W#', '', $value);
    }
}
