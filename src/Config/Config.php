<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
    public const AUTOLOAD = 'autoload';
    public const CACHE_ENV = 'cache-env';
    public const COMMAND_STEPS = 'command-steps';
    public const COMPOSER_UPDATED_PACKAGES = 'composer-updated-packages';
    public const CONTENT_DEV_DIR = 'content-dev-dir';
    public const CONTENT_DEV_OPERATION = 'content-dev-op';
    public const CUSTOM_STEPS = 'custom-steps';
    public const DROPINS = 'dropins';
    public const EARLY_HOOKS_FILE = 'early-hook-file';
    public const ENV_BOOTSTRAP_DIR = 'env-bootstrap-dir';
    public const ENV_DIR = 'env-dir';
    public const ENV_EXAMPLE = 'env-example';
    public const ENV_FILE = 'env-file';
    public const INSTALL_WP_CLI = 'install-wp-cli';
    public const IS_COMPOSER_UPDATE = 'is-composer-update';
    public const IS_COMPOSER_INSTALL = 'is-composer-install';
    public const IS_WPSTARTER_COMMAND = 'is-wpstarter-command';
    public const IS_WPSTARTER_SELECTED_COMMAND = 'is-wpstarter-selected-command';
    public const MOVE_CONTENT = 'move-content';
    public const PREVENT_OVERWRITE = 'prevent-overwrite';
    public const REGISTER_THEME_FOLDER = 'register-theme-folder';
    public const REQUIRE_WP = 'require-wp';
    public const SCRIPTS = 'scripts';
    public const SKIP_DB_CHECK = 'skip-db-check';
    public const SKIP_STEPS = 'skip-steps';
    public const TEMPLATES_DIR = 'templates-dir';
    public const UNKNOWN_DROPINS = 'unknown-dropins';
    public const WP_CLI_COMMANDS = 'wp-cli-commands';
    public const WP_CLI_FILES = 'wp-cli-files';
    public const WP_VERSION = 'wp-version';

    public const DEFAULTS = [
        self::AUTOLOAD => 'wpstarter-autoload.php',
        self::CACHE_ENV => true,
        self::COMMAND_STEPS => null,
        self::COMPOSER_UPDATED_PACKAGES => [],
        self::CONTENT_DEV_OPERATION => ContentDevStep::OP_SYMLINK,
        self::CONTENT_DEV_DIR => 'content-dev',
        self::CUSTOM_STEPS => null,
        self::DROPINS => null,
        self::EARLY_HOOKS_FILE => '',
        self::ENV_BOOTSTRAP_DIR => null,
        self::ENV_DIR => null,
        self::ENV_EXAMPLE => true,
        self::ENV_FILE => '.env',
        self::INSTALL_WP_CLI => true,
        self::IS_COMPOSER_UPDATE => null,
        self::IS_COMPOSER_INSTALL => null,
        self::IS_WPSTARTER_COMMAND => null,
        self::IS_WPSTARTER_SELECTED_COMMAND => null,
        self::MOVE_CONTENT => false,
        self::PREVENT_OVERWRITE => null,
        self::REGISTER_THEME_FOLDER => false,
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

    public const VALIDATION_MAP = [
        self::AUTOLOAD => 'validatePath',
        self::CACHE_ENV => 'validateBool',
        self::COMMAND_STEPS => 'validateSteps',
        self::COMPOSER_UPDATED_PACKAGES => 'validateArray',
        self::CONTENT_DEV_OPERATION => 'validateContentDevOperation',
        self::CONTENT_DEV_DIR => 'validatePath',
        self::CUSTOM_STEPS => 'validateSteps',
        self::DROPINS => 'validateDropins',
        self::EARLY_HOOKS_FILE => 'validatePath',
        self::ENV_BOOTSTRAP_DIR => 'validateDirName',
        self::ENV_DIR => 'validatePath',
        self::ENV_EXAMPLE => 'validateBoolOrAskOrUrlOrPath',
        self::ENV_FILE => 'validateFileName',
        self::INSTALL_WP_CLI => 'validateBool',
        self::IS_COMPOSER_UPDATE => 'validateBool',
        self::IS_COMPOSER_INSTALL => 'validateBool',
        self::IS_WPSTARTER_COMMAND => 'validateBool',
        self::IS_WPSTARTER_SELECTED_COMMAND => 'validateBool',
        self::MOVE_CONTENT => 'validateBoolOrAsk',
        self::PREVENT_OVERWRITE => 'validateOverwrite',
        self::REGISTER_THEME_FOLDER => 'validateBoolOrAsk',
        self::REQUIRE_WP => 'validateBool',
        self::TEMPLATES_DIR => 'validatePath',
        self::SCRIPTS => 'validateScripts',
        self::SKIP_DB_CHECK => 'validateBool',
        self::SKIP_STEPS => 'validateArray',
        self::UNKNOWN_DROPINS => 'validateBoolOrAsk',
        self::WP_CLI_COMMANDS => 'validateWpCliCommands',
        self::WP_CLI_FILES => 'validateWpCliCommandsFileList',
        self::WP_VERSION => 'validateWpVersion',
    ];

    /**
     * @var Result[]
     */
    private $configs;

    /**
     * @var array
     */
    private $raw;

    /**
     * @var array<string, callable(mixed):Result>
     */
    private $validationMap = [];

    /**
     * @param array $configs
     * @param Validator $validator
     */
    public function __construct(array $configs, Validator $validator)
    {
        $this->configs = [];
        $this->raw = array_merge(self::DEFAULTS, $configs);

        /** @var string $key */
        foreach (self::VALIDATION_MAP as $key => $method) {
            /** @var callable(mixed):Result $callable */
            $callback = [$validator, $method];
            $this->validationMap[$key] = $callback;
        }
    }

    /**
     * @param string $name
     * @param callable $callback
     * @return Config
     */
    public function appendValidator(string $name, callable $callback): Config
    {
        if (array_key_exists($name, self::VALIDATION_MAP)) {
            throw new \InvalidArgumentException(
                "It is not possible to overwrite default validation callback for {$name}."
            );
        }

        // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
        $this->validationMap[$name] = static function ($value) use ($callback): Result {
            // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
            try {
                $validated = Result::ok($callback($value));
            } catch (\Throwable $error) {
                $validated = Result::error($error);
            }

            return $validated;
        };

        return $this;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return is_string($offset)
            && (array_key_exists($offset, $this->raw) || array_key_exists($offset, $this->configs));
    }

    /**
     * @param string $offset
     * @return Result
     */
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (!is_string($offset)) {
            return;
        }

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
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new \LogicException('Configs can\'t be unset on the fly.');
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return Result
     *
     * @psalm-suppress MissingParamType
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    private function validateValue(string $name, $value): Result
    {
        // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration

        /** @var null|callable(mixed):Result $method */
        $callback = $this->validationMap[$name] ?? null;

        return $callback ? $callback($value) : Result::ok($value);
    }
}
