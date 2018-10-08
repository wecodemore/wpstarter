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
use WeCodeMore\WpStarter\Util\OverwriteHelper;
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
            return $this->validateGlobPathArray($value);
        }

        if (trim(strtolower((string)$value)) === OverwriteHelper::HARD) {
            return Result::ok(OverwriteHelper::HARD);
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
        if (!is_array($value) || !$value) {
            return Result::ok([]);
        }

        $allScripts = [];

        foreach ($value as $name => $scripts) {
            if (!is_string($name)) {
                continue;
            }

            $name = strtolower($name);
            if (!preg_match('~^(?:pre|post)\-.+$~', $name)) {
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
        if ($value === OptionalStep::ASK) {
            return Result::ok($value);
        }

        is_string($value) and $value = trim(strtolower($value));
        if (in_array($value, ContentDevStep::OPERATIONS, true)) {
            return Result::ok($value);
        }

        return $this->validateBool($value)->is(true)
            ? Result::ok(ContentDevStep::OP_SYMLINK)
            : Result::ok(ContentDevStep::OP_NONE);
    }

    /**
     * @param string|array $value
     * @return Result
     */
    public function validateWpCliCommands($value): Result
    {
        if (is_string($value)) {
            $path = $this->validatePath($value);

            return $path->notEmpty()
                ? $this->validateWpCliCommandsFileList($path->unwrap())
                : Result::ok([]);
        }

        if (!is_array($value)) {
            return Result::ok([]);
        }

        $commands = array_reduce(
            $value,
            function (array $commands, $command): array {
                $command = $this->validateWpCliCommand($command);
                $command->notEmpty() and $commands[] = $command->unwrap();

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
            $hasPath = preg_match('~^(.+)(\-\-path=[^ ]+)(.+)?$~', $value, $matches);
            $hasPath and $value = trim($matches[1] . $matches[3]);
            $command = (string)new StringInput($value);
        } catch (\Throwable $exception) {
            return Result::error(new \Error($exception->getMessage(), 0, $exception));
        }

        return Result::ok($command);
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
                    $data = is_array($file) ? WpCli\FileData::fromArray($file) : null;
                    (!$data && is_string($file)) and $data = WpCli\FileData::fromPath($file);
                    $data->valid() and $files[] = $data;

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
        $validPath = $this->validatePath($path);
        if (!$validPath->notEmpty()) {
            return Result::ok([]);
        }

        $fullpath = $validPath->unwrap();
        if (!is_file($fullpath) || !is_readable($fullpath)) {
            return Result::ok([]);
        }

        $extension = strtolower((string)pathinfo($fullpath, PATHINFO_EXTENSION));
        $isJson = $extension === 'json';
        if ($extension !== 'php' && !$isJson) {
            return Result::ok([]);
        }

        $data = $isJson
            ? @json_decode(file_get_contents($fullpath), true)
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
        if (!is_string($value) && !is_int($value)) {
            return Result::error();
        }

        $value = (string)$value;

        if (!preg_match('/^[0-9]+/', $value)) {
            return Result::error();
        }

        $noAlpha = explode('-', $value);
        $parts = array_map('intval', array_filter(explode('.', $noAlpha[0]), 'is_numeric'));
        if ($parts[0] > 9) {
            return Result::error();
        }

        return Result::ok(implode('.', array_slice(array_pad($parts, 3, 0), 0, 3)));
    }

    /**
     * @param mixed $value
     * @return Result
     */
    public function validateBoolOrAskOrUrlOrPath($value): Result
    {
        $boolOrAskOrUrl = $this->validateBoolOrAskOrUrl($value);
        if ($boolOrAskOrUrl->notEmpty()) {
            return $boolOrAskOrUrl;
        }

        return $this->validatePath($value);
    }

    /**
     * @param mixed $value
     * @return Result
     */
    public function validateBoolOrAskOrUrl($value): Result
    {
        $boolOrAsk = $this->validateBoolOrAsk($value);

        if ($boolOrAsk->notEmpty()) {
            return $boolOrAsk;
        }

        if (is_string($value)) {
            return $this->validateUrl(trim(strtolower($value)));
        }

        return Result::error();
    }

    /**
     * @param string|bool $value
     * @return Result
     */
    public function validateBoolOrAsk($value): Result
    {
        if ($value === OptionalStep::ASK) {
            return Result::ok(OptionalStep::ASK);
        }

        return $this->validateBool($value);
    }

    /**
     * @param string|bool $value
     * @return Result
     */
    public function validateUrlOrPath($value): Result
    {
        $url = $this->validateUrl($value);
        if ($url->notEmpty()) {
            return $url;
        }

        return $this->validatePath($value);
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

        if (!$path) {
            return Result::error();
        }

        if (is_file($path) || is_dir($path)) {
            return Result::ok($this->filesystem->normalizePath($path));
        }

        $fullpath = $this->paths->root("/{$path}");

        return is_file($path) || is_dir($path)
            ? Result::ok($this->filesystem->normalizePath($fullpath))
            : Result::error();
    }

    /**
     * @param $value
     * @return Result
     */
    public function validateGlobPath($value): Result
    {
        if (!substr_count($value, '*')) {
            return $this->validatePath($value);
        }

        $path = is_string($value)
            ? filter_var(str_replace('\\', '/', $value), FILTER_SANITIZE_URL)
            : null;

        $paths = @glob($path);
        if (!$paths) {
            return Result::error();
        }

        try {
            $this->validatePathArray($paths)->unwrap();

            return Result::ok($path);
        } catch (\Error $error) {
            return Result::error($error);
        }
    }

    /**
     * @param string[] $value
     * @return Result
     */
    public function validatePathArray($value): Result
    {
        if (!is_array($value) || !$value) {
            return Result::ok([]);
        }

        $validated = [];
        foreach ($value as $maybePath) {
            $path = $this->validatePath($maybePath);
            $path->notEmpty() and $validated[] = $path->unwrap();
        }

        return Result::ok($validated);
    }

    /**
     * @param string[] $value
     * @return Result
     */
    public function validateGlobPathArray($value): Result
    {
        if (!is_array($value) || !$value) {
            return Result::ok([]);
        }

        $validated = [];
        foreach ($value as $maybePath) {
            $this->validateGlobPath($maybePath)->notEmpty() and $validated[] = $maybePath;
        }

        return $validated ? Result::ok($validated) : Result::ok([]);
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
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($bool === null) {
            return Result::error();
        }

        return Result::ok($bool);
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
