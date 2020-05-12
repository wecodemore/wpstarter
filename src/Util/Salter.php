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
    const CHARS_1 = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const CHARS_2 = ' =,.;:/?!|@#$%^&*()-_[]{}<>~`+';

    const KEYS = [
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT',
    ];

    /**
     * @var string
     */
    private $chars;

    /**
     * @var int
     */
    private $max;

    /**
     * @var array<string,string>
     */
    private $result;

    public function __construct()
    {
        $this->chars = self::CHARS_1 . self::CHARS_2;
        $this->max = strlen($this->chars) - 1;
    }

    /**
     * Build random keys.
     *
     * @return array
     */
    public function keys(): array
    {
        if (!is_array($this->result)) {
            $this->result = [];
            foreach (self::KEYS as $key) {
                $this->result[$key] = $this->buildKey(64);
            }
        }

        return $this->result;
    }

    /**
     * Build random key.
     *
     * @param int $length
     * @return string
     */
    private function buildKey(int $length): string
    {
        $key = '';
        while (strlen($key) < $length) {
            $key .= $this->chars[random_int(0, $this->max)];
        }

        return $key;
    }
}
