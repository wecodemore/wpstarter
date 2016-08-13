<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
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
     * @var callable
     */
    private $randCb;

    /**
     * Build random keys.
     *
     * @return array
     */
    public function keys()
    {
        if (!is_array($this->result)) {
            $this->result = [];
            $this->randCb = function_exists('mt_rand') ? 'mt_rand' : 'rand';
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
        $cb = $this->randCb;
        for ($i = 0; $i < $length; $i++) {
            $key .= self::CHARS[$cb(0, 91)];
        }

        return $key;
    }
}
