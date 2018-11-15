<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Config;

use WeCodeMore\WpStarter\Step\ContentDevStep;

/**
 * Data storage for configuration.
 *
 * A single place that can be used to access validated configuration read from JSON configuration,
 * but also to pass arbitrary data across steps.
 */
final class Config implements \ArrayAccess
{
    const AUTOLOAD = 'autoload';
    const CONTENT_DEV_OPERATION = 'content-dev-op';
    const CACHE_ENV = 'cache-env';
    const CONTENT_DEV_DIR = 'content-dev-dir';
    const COMMAND_STEPS = 'command-steps';
    const CUSTOM_STEPS = 'custom-steps';
    const DROPINS = 'dropins';
    const EARLY_HOOKS_FILE = 'early-hook-file';
    const ENV_BOOTSTRAP_DIR = 'env-bootstrap-dir';
    const ENV_DIR = 'env-dir';
    const ENV_EXAMPLE = 'env-example';
    const ENV_FILE = 'env-file';
    const INSTALL_WP_CLI = 'install-wp-cli';
    const MOVE_CONTENT = 'move-content';
    const PREVENT_OVERWRITE = 'prevent-overwrite';
    const REGISTER_THEME_FOLDER = 'register-theme-folder';
    const REQUIRE_WP = 'require-wp';
    const SCRIPTS = 'scripts';
    const SKIP_DB_CHECK = 'skip-db-check';
    const SKIP_STEPS = 'skip-steps';
    const TEMPLATES_DIR = 'templates-dir';
    const UNKNOWN_DROPINS = 'unknown-dropins';
    const WP_CLI_COMMANDS = 'wp-cli-commands';
    const WP_CLI_FILES = 'wp-cli-files';
    const WP_VERSION = 'wp-version';

    const DEFAULTS = [
        self::AUTOLOAD => 'wpstarter-autoload.php',
        self::CACHE_ENV => true,
        self::CONTENT_DEV_OPERATION => ContentDevStep::OP_SYMLINK,
        self::CONTENT_DEV_DIR => 'content-dev',
        self::CUSTOM_STEPS => null,
        self::COMMAND_STEPS => null,
        self::DROPINS => null,
        self::EARLY_HOOKS_FILE => '',
        self::ENV_BOOTSTRAP_DIR => null,
        self::ENV_DIR => null,
        self::ENV_EXAMPLE => true,
        self::ENV_FILE => '.env',
        self::INSTALL_WP_CLI => true,
        self::MOVE_CONTENT => false,
        self::PREVENT_OVERWRITE => null,
        self::REGISTER_THEME_FOLDER => true,
        self::REQUIRE_WP => true,
        self::SCRIPTS => null,
        self::SKIP_DB_CHECK => false,
        self::SKIP_STEPS => null,
        self::TEMPLATES_DIR => null,
        self::UNKNOWN_DROPINS => false,
        self::WP_CLI_COMMANDS => null,
        self::WP_CLI_FILES => null,
        self::WP_VERSION => null,
    ];

    const VALIDATION_MAP = [
        self::AUTOLOAD => 'validatePath',
        self::CACHE_ENV => 'validateBool',
        self::CONTENT_DEV_OPERATION => 'validateContentDevOperation',
        self::CONTENT_DEV_DIR => 'validatePath',
        self::CUSTOM_STEPS => 'validateSteps',
        self::COMMAND_STEPS => 'validateSteps',
        self::DROPINS => 'validateDropins',
        self::EARLY_HOOKS_FILE => 'validateFileName',
        self::ENV_BOOTSTRAP_DIR => 'validateDirName',
        self::ENV_DIR => 'validatePath',
        self::ENV_EXAMPLE => 'validateBoolOrAskOrUrlOrPath',
        self::ENV_FILE => 'validateFileName',
        self::INSTALL_WP_CLI => 'validateBool',
        self::MOVE_CONTENT => 'validateBoolOrAsk',
        self::PREVENT_OVERWRITE => 'validateOverwrite',
        self::REGISTER_THEME_FOLDER => 'validateBoolOrAsk',
        self::REQUIRE_WP => 'validateBool',
        self::TEMPLATES_DIR => 'validatePath',
        self::SCRIPTS => 'validateScripts',
        self::SKIP_DB_CHECK => 'validateBool',
        self::SKIP_STEPS => 'validateSteps',
        self::UNKNOWN_DROPINS => 'validateBoolOrAsk',
        self::WP_CLI_COMMANDS => 'validateWpCliCommands',
        self::WP_CLI_FILES => 'validateWpCliCommandsFileList',
        self::WP_VERSION => 'validateWpVersion',
    ];

    /**
     * @var array
     */
    private $configs;

    /**
     * @var array
     */
    private $raw;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var array
     */
    private $validationMap;

    /**
     * @param array $configs
     * @param Validator $validator
     */
    public function __construct(array $configs, Validator $validator)
    {
        if (is_array($this->configs)) {
            return;
        }

        $this->configs = [];
        $this->validator = $validator;
        $this->raw = array_merge(self::DEFAULTS, $configs);
        $this->validationMap = self::VALIDATION_MAP;
    }

    /**
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->raw) || array_key_exists($offset, $this->configs);
    }

    /**
     * @param string $name
     * @param callable $validator
     * @return Config
     */
    public function appendValidator(string $name, callable $validator): Config
    {
        if (array_key_exists($name, self::VALIDATION_MAP)) {
            throw new \InvalidArgumentException(
                "It is not possible to overwrite default validation callback for {$name}."
            );
        }

        $this->validationMap[$name] = $validator;

        return $this;
    }

    /**
     * @param string $offset
     * @return Result
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return Result::none();
        }

        if (array_key_exists($offset, $this->configs)) {
            return $this->configs[$offset];
        }

        $this->configs[$offset] = $this->validateValue($offset, $this->raw[$offset]);
        unset($this->raw[$offset]);

        return $this->configs[$offset];
    }

    /**
     * The reason for this to exist is that because steps have access to Config, adding additional
     * arbitrary values to it means steps can "communicate".
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $isNew = !$this->offsetExists($offset);
        $currentValue = $isNew ? null : $this->offsetGet($offset)->unwrapOrFallback();
        $isEmpty = $currentValue === null;
        $isWritable = !$isEmpty
            && array_key_exists($offset, self::DEFAULTS)
            && self::DEFAULTS[$offset] === $currentValue;

        if (!$isNew && !$isEmpty && !$isWritable) {
            throw new \BadMethodCallException(
                sprintf(
                    '%s setting cannot be set, it already set with a non-default value.',
                    $offset
                )
            );
        }

        $this->configs[$offset] = $this->validateValue($offset, $value);
    }

    /**
     * Disabled. Class is append-only.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('Configs can\'t be unset on the fly.');
    }

    /**
     * @param string $name
     * @param $value
     * @return Result
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    private function validateValue(string $name, $value): Result
    {
        // phpcs:enable

        /** @var string|callable $method */
        $method = $this->validationMap[$name] ?? null;
        if (!$method) {
            return Result::ok($value);
        }

        if (array_key_exists($name, self::VALIDATION_MAP)) {
            return ([$this->validator, $method])($value);
        }

        return $this->validator->validateCustom($method, $value);
    }
}
