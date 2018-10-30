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
 * Using this object instead of plain values or just throw exception allows a better error handling,
 * easy and clean "fallback" in case of error and type uniformity in return type.
 *
 * Unfortunately, PHP has no generics at this time so it isn't possible to have type safety for the
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
     * @var boolean
     */
    private $promise = false;

    /**
     * @param mixed $value
     * @return Result
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public static function ok($value): Result
    {
        // phpcs:enable
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
     * @param callable $provider
     * @return Result
     */
    public static function promise(callable $provider): Result
    {
        $instance = new static($provider);
        $instance->promise = true;

        return $instance;
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
        if ($value instanceof Result) {
            $this->value = $value->value;
            $this->error = $value->error;

            return;
        }

        $this->value = $value;
        $this->error = $error;
    }

    /**
     * @return bool
     */
    public function notEmpty(): bool
    {
        $this->maybeResolve();

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
        $this->maybeResolve();

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
        $this->maybeResolve();

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
        $this->maybeResolve();

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
        $this->maybeResolve();

        // phpcs:enable

        return $this->notEmpty() ? $this->value : $fallback;
    }

    /**
     * @return mixed
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    public function unwrap()
    {
        $this->maybeResolve();

        // phpcs:enable
        if ($this->error) {
            throw $this->error;
        }

        return $this->value;
    }

    /**
     * @return void
     */
    private function maybeResolve()
    {
        if (!$this->promise) {
            return;
        }

        $this->promise = false;

        /** @var callable $resolver */
        $resolver = $this->value;

        try {
            $value = $resolver();

            if ($value instanceof Result) {
                $this->error = $value->error;
                $this->value = $this->error ? null : $value->value;

                return;
            }

            $this->value = $value;
        } catch (\Throwable $throwable) {
            $this->value = null;
            $this->error = new \Error($throwable->getMessage(), 0, $throwable);
        }
    }
}
