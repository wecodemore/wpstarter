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

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\StringInput;
use WeCodeMore\WpStarter\Step\ContentDevStep;
use WeCodeMore\WpStarter\Step\GitignoreStep;
use WeCodeMore\WpStarter\Step\StepInterface;
use WeCodeMore\WpStarter\PhpCliTool;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class Config implements \ArrayAccess
{
    const COMPOSER_CONFIG = 'composer';
    const CONTENT_DEV_OPERATION = 'content-dev-op';
    const CONTENT_DEV_DIR = 'content-dev-dir';
    const CUSTOM_STEPS = 'custom-steps';
    const DROPINS = 'dropins';
    const EARLY_HOOKS_FILE = 'early-hook-file';
    const ENV_EXAMPLE = 'env-example';
    const ENV_FILE = 'env-file';
    const GITIGNORE = 'gitignore';
    const INSTALL_WP_CLI = 'install-wp-cli';
    const INSTALL_ROBO = 'install-robo';
    const MOVE_CONTENT = 'move-content';
    const MU_PLUGIN_LIST = 'mu-plugin-list';
    const PREVENT_OVERWRITE = 'prevent-overwrite';
    const REGISTER_THEME_FOLDER = 'register-theme-folder';
    const ROBO_CONFIG = 'robo-config';
    const ROBO_EXECUTOR = 'robo-executor';
    const ROBO_FILE = 'robo-file';
    const SCRIPS = 'scripts';
    const UNKWOWN_DROPINS = 'unknown-dropins';
    const VERBOSITY = 'verbosity';
    const WP_CLI_COMMANDS = 'wp-cli-commands';
    const WP_CLI_EXECUTOR = 'wp-cli-executor';
    const WP_VERSION = 'wp-version';

    const DEFAULTS = [
        self::COMPOSER_CONFIG       => [],
        self::CONTENT_DEV_OPERATION => null,
        self::CONTENT_DEV_DIR       => 'content-dev',
        self::CUSTOM_STEPS          => [],
        self::DROPINS               => [],
        self::EARLY_HOOKS_FILE      => '',
        self::ENV_EXAMPLE           => true,
        self::ENV_FILE              => '.env',
        self::GITIGNORE             => true,
        self::INSTALL_WP_CLI        => true,
        self::INSTALL_ROBO          => true,
        self::MOVE_CONTENT          => false,
        self::MU_PLUGIN_LIST        => [],
        self::PREVENT_OVERWRITE     => ['.gitignore'],
        self::REGISTER_THEME_FOLDER => true,
        self::ROBO_EXECUTOR         => null,
        self::ROBO_CONFIG           => null,
        self::ROBO_FILE             => null,
        self::SCRIPS                => [],
        self::UNKWOWN_DROPINS       => 'ask',
        self::VERBOSITY             => 1,
        self::WP_CLI_COMMANDS       => [],
        self::WP_CLI_EXECUTOR       => null,
        self::WP_VERSION            => '0.0.0',
    ];

    const VALIDATION_MAP = [
        self::COMPOSER_CONFIG       => 'validateArray',
        self::CONTENT_DEV_OPERATION => 'validateContentDevOperation',
        self::CONTENT_DEV_DIR       => 'validatePath',
        self::CUSTOM_STEPS          => 'validateSteps',
        self::DROPINS               => 'validatePathArray',
        self::EARLY_HOOKS_FILE      => 'validatePath',
        self::ENV_EXAMPLE           => 'validateBoolOrAskOrUrl',
        self::ENV_FILE              => 'validatePath',
        self::GITIGNORE             => 'validateGitignore',
        self::INSTALL_WP_CLI        => 'validateBool',
        self::INSTALL_ROBO          => 'validateBool',
        self::MOVE_CONTENT          => 'validateBoolOrAsk',
        self::MU_PLUGIN_LIST        => 'validatePathArray',
        self::PREVENT_OVERWRITE     => 'validateOverwrite',
        self::REGISTER_THEME_FOLDER => 'validateBoolOrAsk',
        self::ROBO_CONFIG           => 'validatePath',
        self::ROBO_EXECUTOR         => 'validateCliExecutor',
        self::ROBO_FILE             => 'validatePath',
        self::SCRIPS                => 'validateScripts',
        self::UNKWOWN_DROPINS       => 'validateBoolOrAsk',
        self::VERBOSITY             => 'validateVerbosity',
        self::WP_CLI_COMMANDS       => 'validateWpCliCommands',
        self::WP_CLI_EXECUTOR       => 'validateCliExecutor',
        self::WP_VERSION            => 'validateWpVersion',
    ];

    const BOOLEANS = [true, false, 1, 0, 'true', 'false', '1', '0', 'yes', 'no', 'on', 'off'];

    /**
     * @var array
     */
    private $configs;

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $this->validate($configs);
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
        if ($this->offsetExists($name) && $this->offsetGet($name) !== null) {
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

    /**
     * @param  array $configs
     * @return array
     *
     * @see \WeCodeMore\WpStarter\Utils\Config::validateArray()
     * @see \WeCodeMore\WpStarter\Utils\Config::validateGitignore()
     * @see \WeCodeMore\WpStarter\Utils\Config::validateBoolOrAskOrUrl()
     * @see \WeCodeMore\WpStarter\Utils\Config::validateBoolOrAsk()
     * @see \WeCodeMore\WpStarter\Utils\Config::validatePath()
     * @see \WeCodeMore\WpStarter\Utils\Config::validatePathArray()
     * @see \WeCodeMore\WpStarter\Utils\Config::validateContentDevOperation()
     * @see \WeCodeMore\WpStarter\Utils\Config::validateOverwrite()
     * @see \WeCodeMore\WpStarter\Utils\Config::validateVerbosity()
     * @see \WeCodeMore\WpStarter\Utils\Config::validateSteps()
     * @see \WeCodeMore\WpStarter\Utils\Config::validateScripts()
     * @see \WeCodeMore\WpStarter\Utils\Config::validateWpCliCommands()
     * @see \WeCodeMore\WpStarter\Utils\Config::validateCliExecutor()
     * @see \WeCodeMore\WpStarter\Utils\Config::validateWpVersion()
     */
    private function validate(array $configs)
    {
        $parsed = [];
        $configs = array_merge(self::DEFAULTS, $configs);

        foreach ($configs as $key => $value) {

            if (!array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            if (array_key_exists($key, self::VALIDATION_MAP)) {
                /** @var callable $validate */
                $validateCb = [$this, self::VALIDATION_MAP[$key]];
                $value = $validateCb($value);
                ($value === null && self::DEFAULTS[$key] !== null) and $value = self::DEFAULTS[$key];
            }

            $parsed[$key] = $value;
        }

        $parsed[self::REGISTER_THEME_FOLDER] and $parsed[self::MOVE_CONTENT] = false;
        
        if ($parsed[self::CONTENT_DEV_OPERATION] === null) {
            $parsed[self::CONTENT_DEV_OPERATION] = $parsed[self::CONTENT_DEV_DIR]
                ? ContentDevStep::OP_SYMLINK
                : ContentDevStep::OP_NONE;
        }

        return $parsed;
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
     * @return bool|string|array
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

        if (in_array($value, ContentDevStep::OPERATIONS, true) || $value === 'ask') {
            return $value;
        }

        $bool = $this->validateBool($value);
        ($bool === true) and $bool = ContentDevStep::OP_SYMLINK;

        return $bool;
    }

    /**
     * @param $value
     * @return array
     */
    private function validateWpCliCommands($value)
    {
        if (is_string($value)) {
            return $this->validateWpCliCommandsFile($value);
        }

        if (!is_array($value)) {
            return [];
        }

        return array_reduce($value, function (array $commands, $command) {
            try {

                if (is_string($command)) {
                    strpos($command, 'wp ') === 0 and $command = substr($command, 3);
                    $command = (string)new StringInput($command);
                    $hasPath = preg_match('~(.*?)--path=([^ ]+)?(.*?)$~', $command, $matches);
                    $commands[] = $hasPath ? trim($matches[1] . $matches[3]) : $command;
                }

                return $commands;
            } catch (InvalidArgumentException $e) {
                return $commands;
            }
        }, []);
    }

    /**
     * @param string $path
     * @return array
     */
    private function validateWpCliCommandsFile($path)
    {

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $isPhp = $extension === 'php';
        $isJson = $extension === 'json';
        if (!$isPhp && !$isJson) {
            return [];
        }

        $fullpath = getcwd() . "/{$path}";
        if (!is_file($fullpath) || !is_readable($fullpath)) {
            return [];
        }

        $data = $isJson
            ? @json_decode(file_get_contents($fullpath))
            : @include $fullpath;

        return $this->validateWpCliCommands((array)$data);
    }

    /**
     * @param $value
     * @return PhpCliTool\CommandExecutor|null
     */
    private function validateCliExecutor($value)
    {
        return $value instanceof PhpCliTool\CommandExecutor ? $value : null;
    }

    /**
     * @param $value
     * @return string
     */
    private function validateWpVersion($value)
    {
        return is_string($value) && preg_match('/^[0-9]{1,2}\.[0-9]+\.[0-9]+$/', $value)
            ? $value
            : '0.0.0';

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
     * @param $value
     * @return array
     */
    private function validateArray($value)
    {
        if ($value instanceof \stdClass) {
            $value = get_object_vars($value);
        }

        return is_array($value) ? $value : [];
    }
}
