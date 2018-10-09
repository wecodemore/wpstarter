<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Config;

/**
 * Value object used as a result for all the validation operation.
 *
 * Using this object instead of plain values or just throw exception allow a better error handling,
 * easy and clean "fallback" in case of error and type uniformity in retun type.
 *
 * Unfortuntately, PHP has no generics at this time so it isn't possible to have type safety for the
 * wrapped value.
 */
final class Result
{
    /**
     * @var null
     */
    private $value;

    /**
     * @var \Error
     */
    private $error;

    /**
     * @param mixed $value
     * @return Result
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public static function ok($value): Result
    {
        // phpcs:enable

        if ($value instanceof Result) {
            return $value->error ? new static(null, $value->error) : new static($value->value);
        }

        return new static($value);
    }

    /**
     * @return Result
     */
    public static function none(): Result
    {
        return new static();
    }

    /**
     * @param \Error|null $error
     * @return Result
     */
    public static function error(\Error $error = null): Result
    {
        return new static(null, $error ?: new \Error('Error.'));
    }

    /**
     * @param string $message
     * @return Result
     */
    public static function errored(string $message): Result
    {
        return static::error(new \Error($message));
    }

    /**
     * @param null $value
     * @param \Error|null $error
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    private function __construct($value = null, \Error $error = null)
    {
        // phpcs:enable

        $this->value = $value;
        $this->error = $error;
    }

    /**
     * @return bool
     */
    public function notEmpty(): bool
    {
        // phpcs:enable

        return $this->error ? false : $this->value !== null;
    }

    /**
     * @param mixed $compare
     * @return bool
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function is($compare): bool
    {
        // phpcs:enable

        return $this->error ? false : $this->value === $compare;
    }

    /**
     * @param mixed $compare
     * @return bool
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function not($compare): bool
    {
        // phpcs:enable

        return !$this->is($compare);
    }

    /**
     * @param mixed $thing
     * @param array $things
     * @return bool
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function either($thing, ...$things): bool
    {
        // phpcs:enable

        array_unshift($things, $thing);

        return $this->error ? false : in_array($this->value, $things, true);
    }

    /**
     * @param null $fallback
     * @return mixed
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    public function unwrapOrFallback($fallback = null)
    {
        // phpcs:enable

        return $this->error ? $fallback : $this->value;
    }

    /**
     * @return mixed
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    public function unwrap()
    {
        // phpcs:enable
        if ($this->error) {
            throw $this->error;
        }

        return $this->value;
    }
}
