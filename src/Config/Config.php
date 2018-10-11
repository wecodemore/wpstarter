<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Config;

use WeCodeMore\WpStarter\Step\ContentDevStep;
use WeCodeMore\WpStarter\Step\OptionalStep;

final class Config implements \ArrayAccess
{
    const COMPOSER_CONFIG = 'composer';
    const CONTENT_DEV_OPERATION = 'content-dev-op';
    const CONTENT_DEV_DIR = 'content-dev-dir';
    const CUSTOM_STEPS = 'custom-steps';
    const DROPINS = 'dropins';
    const EARLY_HOOKS_FILE = 'early-hook-file';
    const ENV_BOOTSTRAP_DIR = 'env-bootstrap-dir';
    const ENV_EXAMPLE = 'env-example';
    const ENV_FILE = 'env-file';
    const INSTALL_WP_CLI = 'install-wp-cli';
    const MOVE_CONTENT = 'move-content';
    const MU_PLUGIN_LIST = 'mu-plugin-list';
    const PREVENT_OVERWRITE = 'prevent-overwrite';
    const REGISTER_THEME_FOLDER = 'register-theme-folder';
    const REQUIRE_WP = 'require-wp';
    const SCRIPTS = 'scripts';
    const SKIP_STEPS = 'skip-steps';
    const TEMPLATES_DIR = 'templates-dir';
    const UNKWOWN_DROPINS = 'unknown-dropins';
    const WP_CLI_COMMANDS = 'wp-cli-commands';
    const WP_CLI_FILES = 'wp-cli-files';
    const WP_CLI_EXECUTOR = 'wp-cli-executor';
    const WP_VERSION = 'wp-version';

    const DEFAULTS = [
        self::COMPOSER_CONFIG => null,
        self::CONTENT_DEV_OPERATION => ContentDevStep::OP_SYMLINK,
        self::CONTENT_DEV_DIR => 'content-dev',
        self::CUSTOM_STEPS => null,
        self::DROPINS => null,
        self::EARLY_HOOKS_FILE => '',
        self::ENV_BOOTSTRAP_DIR => null,
        self::ENV_EXAMPLE => true,
        self::ENV_FILE => '.env',
        self::INSTALL_WP_CLI => true,
        self::MOVE_CONTENT => false,
        self::MU_PLUGIN_LIST => null,
        self::PREVENT_OVERWRITE => null,
        self::REGISTER_THEME_FOLDER => true,
        self::REQUIRE_WP => true,
        self::SCRIPTS => null,
        self::SKIP_STEPS => null,
        self::TEMPLATES_DIR => null,
        self::UNKWOWN_DROPINS => OptionalStep::ASK,
        self::WP_CLI_COMMANDS => null,
        self::WP_CLI_FILES => null,
        self::WP_CLI_EXECUTOR => null,
        self::WP_VERSION => null,
    ];

    const VALIDATION_MAP = [
        self::COMPOSER_CONFIG => 'validateArray',
        self::CONTENT_DEV_OPERATION => 'validateContentDevOperation',
        self::CONTENT_DEV_DIR => 'validatePath',
        self::CUSTOM_STEPS => 'validateSteps',
        self::DROPINS => 'validatePathArray',
        self::EARLY_HOOKS_FILE => 'validateFileName',
        self::ENV_BOOTSTRAP_DIR => 'validateDirName',
        self::ENV_EXAMPLE => 'validateBoolOrAskOrUrlOrPath',
        self::ENV_FILE => 'validateFileName',
        self::INSTALL_WP_CLI => 'validateBool',
        self::MOVE_CONTENT => 'validateBoolOrAsk',
        self::MU_PLUGIN_LIST => 'validatePathArray',
        self::PREVENT_OVERWRITE => 'validateOverwrite',
        self::REGISTER_THEME_FOLDER => 'validateBoolOrAsk',
        self::REQUIRE_WP => 'validateBool',
        self::TEMPLATES_DIR => 'validatePath',
        self::SCRIPTS => 'validateScripts',
        self::SKIP_STEPS => 'validateSteps',
        self::UNKWOWN_DROPINS => 'validateBoolOrAsk',
        self::WP_CLI_COMMANDS => 'validateWpCliCommands',
        self::WP_CLI_FILES => 'validateWpCliCommandsFileList',
        self::WP_CLI_EXECUTOR => 'validateCliExecutor',
        self::WP_VERSION => 'validateWpVersion',
    ];

    /**
     * @var array
     */
    private $configs;

    /**
     * @var Validator
     */
    private $validator;

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

        $configs = array_merge(self::DEFAULTS, $configs);

        foreach ($configs as $key => $value) {
            $this->configs[$key] = $this->validateValue($key, $value)
                ->unwrapOrFallback(self::DEFAULTS[$key] ?? null);
        }

        $this->configs[self::REGISTER_THEME_FOLDER] and $parsed[self::MOVE_CONTENT] = false;

        if ($this->configs[self::CONTENT_DEV_OPERATION] === null) {
            $this->configs[self::CONTENT_DEV_OPERATION] = $this->configs[self::CONTENT_DEV_DIR]
                ? ContentDevStep::OP_SYMLINK
                : ContentDevStep::OP_NONE;
        }
    }

    /**
     * Append-only setter.
     *
     * The reason for this to exist is that because steps have access to Config, adding additional
     * arbitrary values to it means steps can "communicate".
     *
     * @param string $name
     * @param mixed $value
     * @return static
     * @throws \BadMethodCallException
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     */
    public function appendConfig(string $name, $value): Config
    {
        // phpcs:enable

        if ($this->offsetExists($name) && $this->offsetGet($name)->notEmpty()) {
            throw new \BadMethodCallException(
                sprintf(
                    '%s is append-ony: %s config is already set',
                    __CLASS__,
                    $name
                )
            );
        }

        $this->configs[$name] = $this->validateValue($name, $value)
            ->unwrapOrFallback(self::DEFAULTS[$name] ?? null);

        return $this;
    }

    /**
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->configs);
    }

    /**
     * @param string $offset
     * @return Result
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset)
            ? Result::ok($this->configs[$offset])
            : Result::none();
    }

    /**
     * Disabled. Class is append-only.
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('Configs can\'t be set on the fly.');
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

        $method = self::VALIDATION_MAP[$name] ?? null;
        if (!$method) {
            return Result::ok($value);
        }

        return ([$this->validator, $method])($value);
    }
}
