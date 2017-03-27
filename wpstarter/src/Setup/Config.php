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

use WCM\WPStarter\Setup\Steps\GitignoreStep;
use WCM\WPStarter\Setup\Steps\StepInterface;
use Composer\Config as ComposerConfig;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class Config implements \ArrayAccess
{

    const GITIGNORE = 'gitignore';
    const ENV_EXAMPLE = 'env-example';
    const ENV_FILE = 'env-file';
    const MOVE_CONTENT = 'move-content';
    const CONTENT_DEV_OPERATION = 'content-dev-op';
    const CONTENT_DEV_DIR = 'content-dev-dir';
    const REGISTER_THEME_FOLDER = 'register-theme-folder';
    const PREVENT_OVERWRITE = 'prevent-overwrite';
    const DROPINS = 'dropins';
    const UNKWOWN_DROPINS = 'unknown-dropins';
    const VERBOSITY = 'verbosity';
    const CUSTOM_STEPS = 'custom-steps';
    const SCRIPS = 'scripts';
    const WP_VERSION = 'wp-version';
    const COMPOSER_CONFIG = 'composer';

    const DEFAULTS = [
        self::GITIGNORE             => true,
        self::ENV_EXAMPLE           => true,
        self::ENV_FILE              => '.env',
        self::MOVE_CONTENT          => false,
        self::CONTENT_DEV_OPERATION => 'symlink',
        self::CONTENT_DEV_DIR       => 'content-dev',
        self::REGISTER_THEME_FOLDER => true,
        self::PREVENT_OVERWRITE     => ['.gitignore'],
        self::DROPINS               => [],
        self::UNKWOWN_DROPINS       => 'ask',
    ];

    const VALIDATION_MAP = [
        self::GITIGNORE             => 'validateGitignore',
        self::ENV_EXAMPLE           => 'validateBoolOrAskOrUrl',
        self::ENV_FILE              => 'validatePath',
        self::MOVE_CONTENT          => 'validateBoolOrAsk',
        self::CONTENT_DEV_OPERATION => 'validateContentDevOperation',
        self::CONTENT_DEV_DIR       => 'validatePath',
        self::REGISTER_THEME_FOLDER => 'validateBoolOrAsk',
        self::PREVENT_OVERWRITE     => 'validateOverwrite',
        self::DROPINS               => 'validatePathArray',
        self::UNKWOWN_DROPINS       => 'validateBoolOrAsk',
        self::VERBOSITY             => 'validateVerbosity',
        self::CUSTOM_STEPS          => 'validateSteps',
        self::SCRIPS                => 'validateScripts'
    ];

    const BOOLEANS = [true, false, 1, 0, 'true', 'false', '1', '0', 'yes', 'no', 'on', 'off'];

    /**
     * @var array
     */
    private $configs;

    /**
     * Constructor.
     *
     * @param array $configs
     * @param ComposerConfig $composer_config
     */
    public function __construct(array $configs, ComposerConfig $composer_config)
    {
        $this->configs = $this->validate($configs);
        $this->configs[self::COMPOSER_CONFIG] = $composer_config->all();
    }

    /**
     * Append-only setter.
     *
     * Allows to use config class as a DTO among steps.
     *
     * @param string $name
     * @param mixed $value
     * @return static
     * @throws \BadMethodCallException
     */
    public function appendConfig($name, $value)
    {
        if ($this->offsetExists($name)) {
            throw new \BadMethodCallException(
                sprintf(
                    '%s is append-ony: %s config is already set',
                    __CLASS__,
                    $name
                )
            );
        }

        if (array_key_exists($name, self::VALIDATION_MAP)) {
            /** @var callable $validate */
            $validate = [$this, self::VALIDATION_MAP[$name]];
            $value = $validate($validate);
        }

        $value === null or $this->configs[$name] = $value;

        return $this;
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
     * @see \WCM\WPStarter\Setup\Config::validateSteps()
     * @see \WCM\WPStarter\Setup\Config::validateScripts()
     */
    private function validate(array $configs)
    {
        $parsed = new \ArrayObject(self::DEFAULTS);

        array_walk($configs, function ($value, $key, \ArrayObject $parsed) {

            $validated = $value;
            if (array_key_exists($key, self::VALIDATION_MAP)) {
                /** @var callable $validate */
                $validate = [$this, self::VALIDATION_MAP[$key]];
                $validated = $validate($value);
            }

            $validated === null or $parsed[$key] = $validated;
        }, $parsed);

        $parsed[self::REGISTER_THEME_FOLDER] and $parsed[self::MOVE_CONTENT] = false;

        $wpVersion = empty($configs[self::WP_VERSION]) ? '0.0.0' : $configs[self::WP_VERSION];

        return array_merge($parsed->getArrayCopy(), [self::WP_VERSION => $wpVersion]);
    }

    /**
     * @param $value
     * @return string|bool|array|null
     */
    private function validateGitignore($value)
    {
        if (is_array($value)) {
            $custom = isset($value[GitignoreStep::CUSTOM]) && is_array($value[GitignoreStep::CUSTOM])
                ? array_filter($value[GitignoreStep::CUSTOM], 'is_string')
                : [];

            $default = GitignoreStep::DEFAULTS;
            unset($default[GitignoreStep::CUSTOM]);

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

        return $path ?: null;
    }

    /**
     * @param $value
     * @return array
     */
    private function validatePathArray($value)
    {
        if (!is_array($value)) {
            return [];
        }

        return array_unique(array_filter(array_map([$this, 'validatePath'], $value)));
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
        }

        if (is_string($value)) {
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
        if (strtolower($value) === 'ask') {
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
     * @return array|null
     */
    private function validateSteps($value)
    {
        if (!is_array($value)) {
            return null;
        }

        $interface = StepInterface::class;

        $steps = [];
        foreach ($value as $name => $step) {
            if (is_string($step)) {
                $step = trim($step);
                is_subclass_of($step, $interface, true) and $steps[trim($name)] = $step;
            }
        }

        return $steps ?: null;
    }

    /**
     * @param $value
     * @return array
     */
    private function validateScripts($value)
    {
        if (!is_array($value)) {
            return null;
        }

        $allScripts = [];

        foreach ($value as $name => $scripts) {

            is_string($name) or $name = '';

            if (strpos($name, 'pre-') !== 0 && strpos($name, 'post-') !== 0) {
                continue;
            }

            if (is_callable($scripts)) {
                $allScripts[$name] = [$scripts];
                continue;
            }

            if (is_array($scripts)) {
                $scripts = array_filter($scripts, 'is_callable');
                $scripts and $allScripts[$name] = $scripts;
            }
        }

        return $allScripts ?: null;
    }

    /**
     * @param $value
     * @return bool|null|string
     */
    private function validateContentDevOperation($value)
    {
        is_string($value) and $value = trim(strtolower($value));

        if (in_array($value, ['symlink', 'copy', 'ask'], true)) {
            return $value;
        }

        $bool = $this->validateBool($value);
        ($bool === true) and $bool = null; // when true we return null to force default

        return $bool;
    }

    /**
     * @param $value
     * @return bool
     */
    private function validateBool($value)
    {
        is_string($value) and $value = strtolower($value);

        if (in_array($value, self::BOOLEANS, true)) {
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
        return is_numeric($value) ? (int)$value : null;
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
        return $this->offsetExists($offset) ? $this->configs[$offset] : null;
    }

    /**
     * @inheritdoc
     * @throws \LogicException
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('Configs can\'t be set on the fly.');
    }

    /**
     * @inheritdoc
     * @throws \LogicException
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('Configs can\'t be unset on the fly.');
    }
}
