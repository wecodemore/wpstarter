<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

/**
 * Helper to generate random strings to be used as salt keys in WordPress.
 */
class Salter
{
    public const CHARS_1 = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const CHARS_2 = ' =,.;:/?!|@#$%^&*()-_[]{}<>~`+';

    public const KEYS = [
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT',
    ];

    /** @var int<8, 256> */
    private int $length;

    /** @var array<value-of<Salter::KEYS>, non-falsy-string>|null */
    private ?array $keyValues = null;

    /**
     * @param int $keyLength
     */
    public function __construct(int $keyLength = 64)
    {
        $this->length = min(256, max(8, $keyLength));
    }

    /**
     * @return array<value-of<Salter::KEYS>, non-falsy-string>
     *
     * @phpstan-assert array<value-of<Salter::KEYS>, non-falsy-string> $this->keyValues
     */
    public function keys(): array
    {
        if (!is_array($this->keyValues)) {
            $this->keyValues = [];
            foreach (self::KEYS as $key) {
                $this->keyValues[$key] = $this->buildKey();
            }
        }

        return $this->keyValues;
    }

    /**
     * Build random key.
     *
     * @return non-falsy-string
     */
    private function buildKey(): string
    {
        static $poolOneLength, $poolTwoLength;
        isset($poolOneLength) or $poolOneLength = strlen(self::CHARS_1) - 1;
        isset($poolTwoLength) or $poolTwoLength = strlen(self::CHARS_2) - 1;
        /**
         * @var positive-int $poolOneLength
         * @var positive-int $poolTwoLength
         */

        $key = '';
        for ($i = 0; $i < $this->length; $i++) {
            [$pool, $maxLength] = (random_int(1, 1024) > 512)
                ? [self::CHARS_1, $poolOneLength]
                : [self::CHARS_2, $poolTwoLength];

            $key .= $pool[random_int(0, $maxLength)];
        }

        /** @var non-falsy-string $key */
        return $key; // @phpstan-ignore varTag.nativeType
    }
}
