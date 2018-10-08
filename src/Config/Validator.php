<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Config;

use Composer\Util\Filesystem;
use Symfony\Component\Console\Input\StringInput;
use WeCodeMore\WpStarter\Step\ContentDevStep;
use WeCodeMore\WpStarter\Step\OptionalStep;
use WeCodeMore\WpStarter\Step\Step;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\WpCli;

/**
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
 */
class Validator
{
    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Paths $paths
     * @param Filesystem $filesystem
     */
    public function __construct(Paths $paths, Filesystem $filesystem)
    {
        $this->paths = $paths;
        $this->filesystem = $filesystem;
    }

    /**
     * @param $value
     * @return Result
     */
    public function validateOverwrite($value): Result
    {
        if (is_array($value)) {
            return $this->validatePathArray($value);
        }

        if (trim(strtolower((string)$value)) === 'hard') {
            return Result::ok('hard');
        }

        return $this->validateBoolOrAsk($value);
    }

    /**
     * @param $value
     * @return Result
     */
    public function validateSteps($value): Result
    {
        if (!is_array($value)) {
            return Result::ok([]);
        }

        $steps = [];
        foreach ($value as $name => $step) {
            if (!is_string($step)) {
                continue;
            }

            $step = trim($step);
            is_subclass_of($step, Step::class, true) and $steps[trim($name)] = $step;
        }

        return Result::ok($steps);
    }

    /**
     * @param array $value
     * @return Result
     */
    public function validateScripts($value): Result
    {
        if (!is_array($value)) {
            return Result::ok([]);
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

        return Result::ok($allScripts);
    }

    /**
     * @param string|bool $value
     * @return Result
     */
    public function validateContentDevOperation($value): Result
    {
        is_string($value) and $value = trim(strtolower($value));

        if (in_array($value, ContentDevStep::OPERATIONS, true) || $value === OptionalStep::ASK) {
            return Result::ok($value);
        }

        $bool = $this->validateBool($value);
        ($bool === true) and $bool = ContentDevStep::OP_SYMLINK;

        return Result::ok($bool);
    }

    /**
     * @param string|array $value
     * @return Result
     */
    public function validateWpCliCommands($value): Result
    {
        if (is_file($value)) {
            return $this->validateWpCliCommandsFileList($value);
        }

        if (!is_array($value)) {
            return Result::ok([]);
        }

        $commands = array_reduce(
            $value,
            function (array $commands, $command): array {
                $command = $this->validateWpCliCommand($command)->unwrapOrFallback();
                $command and $commands[] = $command;

                return $commands;
            },
            []
        );

        return Result::ok($commands);
    }

    /**
     * @param string $value
     * @return Result
     */
    public function validateWpCliCommand($value): Result
    {
        if (!is_string($value)) {
            return Result::error();
        }

        strpos($value, 'wp ') === 0 and $value = substr($value, 3);

        try {
            $command = (string)new StringInput($value);
        } catch (\Throwable $exception) {
            return Result::error(new \Error($exception->getMessage(), 0, $exception));
        }

        $hasPath = preg_match('~^(.*?)+(--path=[^ ]+)+(.*?)+$~', $command, $matches);

        return $hasPath ? Result::ok(trim($matches[1] . $matches[3])) : Result::ok($command);
    }

    /**
     * @param string|array $value
     * @return Result
     */
    public function validateWpCliFiles($value): Result
    {
        is_string($value) and $value = (array)$value;

        if (!is_array($value)) {
            return Result::ok([]);
        }

        $files = array_reduce(
            $value,
            function (array $files, $file): array {
                try {
                    $file = is_array($file) ? WpCli\FileData::fromArray($file) : null;
                    (!$file && is_string($file)) and $file = WpCli\FileData::fromPath($file);
                    $file->valid() and $files[] = $file;

                    return $files;
                } catch (\Throwable $exception) {
                    return $files;
                }
            },
            []
        );

        return Result::ok($files);
    }

    /**
     * @param string $path
     * @return Result
     */
    public function validateWpCliCommandsFileList($path): Result
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $isPhp = $extension === 'php';
        $isJson = $extension === 'json';
        if (!$isPhp && !$isJson) {
            return Result::ok([]);
        }

        $fullpath = $this->filesystem->normalizePath(getcwd() . "/{$path}");
        if (!is_file($fullpath) || !is_readable($fullpath)) {
            return Result::ok([]);
        }

        $data = $isJson
            ? @json_decode(file_get_contents($fullpath))
            : @include $fullpath;

        return is_array($data) ? $this->validateWpCliCommands($data) : Result::ok([]);
    }

    /**
     * @param $value
     * @return Result
     */
    public function validateCliExecutor($value): Result
    {
        return $value instanceof WpCli\Executor ? Result::ok($value) : Result::error();
    }

    /**
     * @param string $value
     * @return Result
     */
    public function validateWpVersion($value): Result
    {
        return is_string($value) && preg_match('/^[0-9]{1,2}\.[0-9]+\.[0-9]+$/', $value)
            ? Result::ok($value)
            : Result::ok('0.0.0');
    }

    /**
     * @param mixed $value
     * @return Result
     */
    public function validateEnvExample($value): Result
    {
        $isPath = null;
        $boolOrAsk = $this->validateBoolOrAsk($value);
        if ($boolOrAsk === OptionalStep::ASK) {
            return Result::ok(OptionalStep::ASK);
        }

        $isString = is_string($value);

        $maybeUrl = $isString ? $this->validateUrl($value) : null;
        if ($maybeUrl && $maybeUrl->notEmpty()) {
            return $maybeUrl;
        }

        $isPath = $isString ? $this->validatePath($value) : null;

        return $isPath->notEmpty() ? $isPath : Result::ok($boolOrAsk);
    }

    /**
     * @param $value
     * @return Result
     */
    public function validatePath($value): Result
    {
        $path = is_string($value)
            ? filter_var(str_replace('\\', '/', $value), FILTER_SANITIZE_URL)
            : null;

        $path = $this->filesystem->normalizePath($path);

        if (!$path) {
            return Result::error();
        }

        $realpath = realpath($path);
        if ($realpath) {
            return Result::ok($realpath);
        }

        $fullRealpath = realpath($this->paths->root('/') . $path);

        return $fullRealpath ? Result::ok($fullRealpath) : Result::error();
    }

    /**
     * @param string[] $value
     * @return Result
     */
    public function validatePathArray($value): Result
    {
        if (!is_array($value)) {
            return Result::ok([]);
        }

        $paths = array_unique(array_filter(array_map([$this, 'validatePath'], $value)));

        return Result::ok($paths);
    }

    /**
     * @param mixed $value
     * @return Result
     */
    public function validateBoolOrAskOrUrl($value): Result
    {
        $boolOrAsk = $this->validateBoolOrAsk($value);
        if ($boolOrAsk === OptionalStep::ASK) {
            return Result::ok(OptionalStep::ASK);
        }

        if (is_string($value)) {
            return Result::ok($this->validateUrl(trim(strtolower($value))));
        }

        return Result::ok($boolOrAsk);
    }

    /**
     * @param string|bool $value
     * @return Result
     */
    public function validateBoolOrAsk($value): Result
    {
        if (strtolower($value) === OptionalStep::ASK) {
            return Result::ok(OptionalStep::ASK);
        }

        return $this->validateBool($value);
    }

    /**
     * @param string $value
     * @return Result
     */
    public function validateUrl($value): Result
    {
        $url = filter_var($value, FILTER_VALIDATE_URL) ?: null;

        return $url ? Result::ok($url) : Result::error();
    }

    /**
     * @param $value
     * @return Result
     */
    public function validateBool($value): Result
    {
        return Result::ok((bool)filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    /**
     * @param $value
     * @return Result
     */
    public function validateInt($value): Result
    {
        return is_numeric($value) ? Result::ok((int)$value) : Result::error();
    }

    /**
     * @param array|\stdClass $value
     * @return Result
     */
    public function validateArray($value): Result
    {
        if ($value instanceof \stdClass) {
            $value = get_object_vars($value);
        }

        return is_array($value) ? Result::ok($value) : Result::error();
    }
}
