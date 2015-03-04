<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class Salter
{
    private static $keys = array(
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT',
    );

    private $result;

    public function keys()
    {
        if (! is_array($this->result)) {
            $this->result = array();
            foreach (self::$keys as $key) {
                $this->result[$key] = $this->buildKey(54);
            }
        }

        return $this->result;
    }

    private function buildKey($length)
    {
        $chars = '=,.;:/?|abcdefghijklmnopqrstuvwxyz!@#$%^&*()ABCDEFGHIJKLMNOPQRSTUVWXYZ-_[]{}<>~`+0123456789';
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, 90);
            $key .= $chars[$rand];
        }

        return $key;
    }
}
