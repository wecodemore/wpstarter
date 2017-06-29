<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Utils;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
class Salter
{
    const CHARS = ' =,.;:/?!|@#$%^&*()-_[]{}<>~`+abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * @var array
     */
    private static $keys = [
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
     * @var array
     */
    private $result;

    /**
     * Build random keys.
     *
     * @return array
     */
    public function keys()
    {
        if (!is_array($this->result)) {
            $this->result = [];
            foreach (self::$keys as $key) {
                $this->result[$key] = $this->buildKey(64);
            }
        }

        return $this->result;
    }

    /**
     * Build random key.
     *
     * @param  int $length
     * @return string
     */
    private function buildKey($length)
    {
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= self::CHARS[$this->random_number(0, 91)];
        }

        return $key;
    }

    /**
     * @param $min
     * @param $max
     * @return int
     */
    private function random_number($min, $max)
    {
        /** @var callable $cb */
        static $cb;
        if (!$cb) {
            $cb = function_exists('random_int') ? 'random_int' : null;
            $cb or $cb = function_exists('mt_rand') ? 'mt_rand' : null;
            $cb or $cb = 'rand';
        }

        return $cb($min, $max);
    }
}
