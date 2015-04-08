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

use ArrayAccess;
use LogicException;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class Config implements ArrayAccess
{
    private static $defaults = array(
        'gitignore'             => true,
        'env-example'           => true,
        'move-content'          => false,
        'register-theme-folder' => true,
        'prevent-overwrite'     => array('.gitignore'),
        'verbosity'             => 2,
        'dropins'               => array(),
        'unknown-dropins'       => 'ask',
    );

    private $configs;

    /**
     * Constructor.
     *
     * @param array $configs
     */
    public function __construct($configs)
    {
        $this->configs = $this->validate($configs);
    }

    /**
     * @param  array $configs
     * @return array
     * @use \WCM\WPStarter\Setup\Config::validateGitignore()
     * @use \WCM\WPStarter\Setup\Config::validateBoolOrAskOrUrl()
     * @use \WCM\WPStarter\Setup\Config::validateBoolOrAsk()
     * @use \WCM\WPStarter\Setup\Config::validateBool()
     * @use \WCM\WPStarter\Setup\Config::validateVerbosity()
     */
    private function validate($configs)
    {
        $valid = array('is-root' => $configs['is-root'], 'wp-version' => $configs['is-root']);
        $map = array(
            'gitignore'             => array($this, 'validateGitignore'),
            'env-example'           => array($this, 'validateBoolOrAskOrUrl'),
            'register-theme-folder' => array($this, 'validateBoolOrAsk'),
            'move-content'          => array($this, 'validateBoolOrAsk'),
            'dropins'               => array($this, 'validatePathArray'),
            'unknown-dropins'       => array($this, 'validateBoolOrAsk'),
            'prevent-overwrite'     => array($this, 'validateOverwrite'),
            'verbosity'             => array($this, 'validateVerbosity'),
        );
        $defaults = self::$defaults;
        array_walk($configs, function ($value, $key) use ($map, &$defaults) {
            $result = array_key_exists($key, $defaults) ? call_user_func($map[$key], $value) : null;
            if (! is_null($result)) {
                $defaults[$key] = $result;
            }
        });
        if ($defaults['register-theme-folder']) {
            $defaults['move-content'] = false;
        }

        return array_merge($defaults, $valid);
    }

    /**
     * @param $value
     * @return string|bool|array|null
     */
    private function validateGitignore($value)
    {
        if (is_array($value)) {
            $custom = isset($value['custom']) && is_array($value['custom'])
                ? array_filter($value['custom'], 'is_string')
                : array();
            $default = array(
                'wp'         => true,
                'wp-content' => true,
                'vendor'     => true,
                'common'     => true,
            );
            foreach ($value as $k => $v) {
                if (array_key_exists($k, $default) && $this->validateBool($v) === false) {
                    $default[$k] = false;
                }
            }

            return array_merge(array('custom' => $custom), $default);
        }

        return $this->validateBoolOrAskOrUrl($value);
    }

    /**
     * @param $value
     * @return bool|string
     */
    private function validateOverwrite($value)
    {
        if (is_array($value)) {
            return $this->validatePathArray($value);
        }
        if (trim(strtolower((string) $value)) === 'hard') {
            return 'hard';
        }

        return $this->validateBoolOrAsk($value);
    }

    /**
     * @param $value
     * @return int|null
     */
    private function validateVerbosity($value)
    {
        $int = (int) $this->validateInt($value);

        return $int >= 0 && $int < 3 ? $int : null;
    }

    /**
     * @param $value
     * @return int|void
     */
    private function validatePathArray($value)
    {
        if (is_array($value)) {
            array_walk($value, function (&$path) {
                $path = filter_var(str_replace('\\', '/', $path), FILTER_SANITIZE_URL);
            });

            return array_unique(array_filter($value));
        }
    }

    /**
     * @param $value
     * @return string|bool|null
     */
    private function validateBoolOrAskOrUrl($value)
    {
        $ask = $this->validateBoolOrAsk($value);
        if ($ask === 'ask') {
            return 'ask';
        } elseif (is_string($value)) {
            return $this->validateUrl(trim(strtolower($value)));
        }

        return $ask;
    }

    /**
     * @param $value
     * @return bool|string
     */
    private function validateBoolOrAsk($value)
    {
        $asks = array('ask', 'prompt', 'query', 'interrogate', 'demand');
        if (in_array(trim(strtolower($value)), $asks, true)) {
            return 'ask';
        }

        return $this->validateBool($value);
    }

    /**
     * @param $value
     * @return string|null
     */
    private function validateUrl($value)
    {
        return filter_var($value, FILTER_SANITIZE_URL) ?: null;
    }

    /**
     * @param $value
     * @return bool|null
     */
    private function validateBool($value)
    {
        $booleans = array(true, false, 1, 0, "true", "false", "1", "0", "yes", "no", "on", "off");
        if (in_array(is_string($value) ? strtolower($value) : $value, $booleans, true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
    }

    /**
     * @param $value
     * @return int|null
     */
    private function validateInt($value)
    {
        return is_numeric($value) ? intval($value) : null;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->configs);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->configs[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        throw new LogicException("Configs can't be set on the fly.");
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        throw new LogicException("Configs can't be unset on the fly.");
    }
}
