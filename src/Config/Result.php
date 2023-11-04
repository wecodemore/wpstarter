<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Config;

/**
 * Value object used as a result for all the validation operation.
 *
 * Using this object instead of plain values or just throw exception allows a better error handling,
 * easy and clean "fallback" in case of error and type uniformity in return type.
 *
 * Unfortunately, PHP has no generics at this time, so it isn't possible to have type safety for the
 * wrapped value.
 */
final class Result
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var \Throwable|null
     */
    private $error;

    /**
     * @var boolean
     */
    private $promise = false;

    /**
     * @param mixed $value
     * @return Result
     */
    public static function ok($value): Result
    {
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
     * @param \Throwable|null $error
     * @return Result
     */
    public static function error(?\Throwable $error = null): Result
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
     * @param mixed $value
     * @param \Throwable|null $error
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.IncorrectVoidReturn
     */
    private function __construct($value = null, \Throwable $error = null)
    {
        // phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration.IncorrectVoidReturn
        if ($value instanceof Result) {
            $this->value = $value->value;
            $this->error = $value->error;

            return;
        }

        if ($value instanceof \Throwable && !$error) {
            $error = $value;
            $value = null;
        }

        $this->value = $error ? null : $value;
        $this->error = $error;
    }

    /**
     * @return bool
     */
    public function notEmpty(): bool
    {
        $this->maybeResolve();

        return !$this->error && ($this->value !== null);
    }

    /**
     * @param mixed $compare
     * @return bool
     */
    public function is($compare): bool
    {
        $this->maybeResolve();

        return !$this->error && ($this->value === $compare);
    }

    /**
     * @param mixed $compare
     * @return bool
     */
    public function not($compare): bool
    {
        $this->maybeResolve();

        return !$this->is($compare);
    }

    /**
     * @param mixed $thing
     * @param mixed $things
     * @return bool
     */
    public function either($thing, ...$things): bool
    {
        $this->maybeResolve();

        array_unshift($things, $thing);

        return !$this->error && in_array($this->value, $things, true);
    }

    /**
     * @param mixed $fallback
     * @return mixed
     */
    public function unwrapOrFallback($fallback = null)
    {
        $this->maybeResolve();

        return $this->notEmpty() ? $this->value : $fallback;
    }

    /**
     * @return mixed
     */
    public function unwrap()
    {
        $this->maybeResolve();

        if ($this->error) {
            throw $this->error;
        }

        return $this->value;
    }

    /**
     * @return void
     */
    private function maybeResolve(): void
    {
        if (!$this->promise) {
            return;
        }

        $this->promise = false;

        /** @var callable $resolver */
        $resolver = $this->value;

        try {
            $value = $resolver();

            $resolved = $value;
            $error = null;
            while ($resolved instanceof Result && !$error) {
                $resolved->maybeResolve();
                $error = $resolved->error;
                $resolved = $resolved->value;
            }

            if ($error) {
                $this->error = $error;
                $this->value = null;

                return;
            }

            $this->value = $resolved;
        } catch (\Throwable $error) {
            $this->value = null;
            $this->error = $error;
        }
    }
}
