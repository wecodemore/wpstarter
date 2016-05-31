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
final class Config implements \ArrayAccess
{
    private static $defaults = [
        'gitignore'             => true,
        'env-example'           => true,
        'env-file'              => '.env',
        'move-content'          => false,
        'content-dev'           => 'symlink',
        'content-dev-dir'       => 'content-dev',
        'register-theme-folder' => true,
        'prevent-overwrite'     => ['.gitignore'],
        'verbosity'             => 2,
        'dropins'               => [],
        'custom-steps'          => [],
        'unknown-dropins'       => 'ask',
    ];

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
     * Append-only setter.
     *
     * Allows to use config class as a DTO among steps.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function appendConfig($name, $value)
    {
        if ($this->offsetExists($name)) {
            throw new \BadMethodCallException(
                "%s is append-ony: %s config is already set",
                __CLASS__,
                $name
            );
        }

        $this->configs[$name] = $value;
    }

    /**
     * @param  array $configs
     * @return array
     * @see \WCM\WPStarter\Setup\Config::validateGitignore()
     * @see \WCM\WPStarter\Setup\Config::validateBoolOrAskOrUrl()
     * @see \WCM\WPStarter\Setup\Config::validateBoolOrAsk()
     * @see \WCM\WPStarter\Setup\Config::validatePath()
     * @see \WCM\WPStarter\Setup\Config::validatePathArray()
     * @see \WCM\WPStarter\Setup\Config::validateContentDevOperation()
     * @see \WCM\WPStarter\Setup\Config::validateOverwrite()
     * @see \WCM\WPStarter\Setup\Config::validateVerbosity()
     * @see \WCM\WPStarter\Setup\Config::validateFilename()
     * @see \WCM\WPStarter\Setup\Config::validateSteps()
     */
    private function validate($configs)
    {
        $valid = ['is-root' => $configs['is-root'], 'wp-version' => $configs['wp-version']];
        $map = [
            'gitignore'             => [$this, 'validateGitignore'],
            'env-example'           => [$this, 'validateBoolOrAskOrUrl'],
            'env-file'              => [$this, 'validateFilename'],
            'register-theme-folder' => [$this, 'validateBoolOrAsk'],
            'move-content'          => [$this, 'validateBoolOrAsk'],
            'content-dev-dir'       => [$this, 'validatePath'],
            'content-dev-op'        => [$this, 'validateContentDevOperation'],
            'dropins'               => [$this, 'validatePathArray'],
            'unknown-dropins'       => [$this, 'validateBoolOrAsk'],
            'prevent-overwrite'     => [$this, 'validateOverwrite'],
            'verbosity'             => [$this, 'validateVerbosity'],
            'steps'                 => [$this, 'validateSteps'],
        ];

        $defaults = self::$defaults;

        array_walk($defaults, function (&$value, $key, array $map) use ($configs) {
            if (isset($configs[$key])) {
                $validated = call_user_func($map[$key], $configs[$key]);
                is_null($validated) or $value = $validated;
            }
        }, $map);

        $defaults['register-theme-folder'] and $defaults['move-content'] = false;

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
                : [];
            $default = [
                'wp'         => true,
                'wp-content' => true,
                'vendor'     => true,
                'common'     => true,
            ];
            foreach ($value as $k => $v) {
                if (array_key_exists($k, $default) && $this->validateBool($v) === false) {
                    $default[$k] = false;
                }
            }

            return array_merge(['custom' => $custom], $default);
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
        if (trim(strtolower((string)$value)) === 'hard') {
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
        $int = (int)$this->validateInt($value);

        return $int >= 0 && $int < 3 ? $int : null;
    }

    /**
     * @param $value
     * @return string|null
     */
    private function validatePath($value)
    {
        $path = filter_var(str_replace('\\', '/', $value), FILTER_SANITIZE_URL);

        return $path ? : null;
    }

    /**
     * @param $value
     * @return array
     */
    private function validatePathArray($value)
    {
        if (is_array($value)) {
            array_walk($value, function (&$path) {
                $path = $this->validatePath($path);
            });

            return array_unique(array_filter($value));
        }

        return [];
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
        $asks = ['ask', 'prompt', 'query', 'interrogate', 'demand'];
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
        return filter_var($value, FILTER_SANITIZE_URL) ? : null;
    }

    /**
     * @param $value
     * @return string|null
     */
    private function validateFilename($value)
    {
        $filtered = filter_var($value, FILTER_SANITIZE_URL) ? : null;

        return $filtered ? basename($filtered) : null;
    }

    /**
     * @param $value
     * @return array|null
     */
    private function validateSteps($value)
    {
        if (! is_array($value)) {
            return null;
        }

        $interface = 'WCM\\WPStarter\\Setup\\Steps\\StepInterface';

        $steps = [];
        foreach ($value as $name => $step) {
            if (is_string($step)) {
                $step = trim($step);
                is_subclass_of($step, $interface, true) and $steps[trim($name)] = $step;
            }
        }

        return $steps ? : null;
    }

    /**
     * @param $value
     * @return bool|null|string
     */
    private function validateContentDevOperation($value)
    {
        is_string($value) and $value = strtolower($value);

        if (in_array($value, ['symlink', 'copy'], true)) {
            return $value;
        }

        $bool = $this->validateBool($value);
        $bool === true and $bool = null; // when true we return null to force default

        return $bool;
    }

    /**
     * @param $value
     * @return bool
     */
    private function validateBool($value)
    {
        $booleans = [true, false, 1, 0, "true", "false", "1", "0", "yes", "no", "on", "off"];
        if (in_array(is_string($value) ? strtolower($value) : $value, $booleans, true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return false;
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
        throw new \LogicException("Configs can't be set on the fly.");
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException("Configs can't be unset on the fly.");
    }
}
