<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Config;

use Composer\Util\Filesystem as ComposerFilesystem;
use Symfony\Component\Console\Input\StringInput;
use WeCodeMore\WpStarter\Step\OptionalStep;
use WeCodeMore\WpStarter\Util\DbChecker;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\WpVersion;
use WeCodeMore\WpStarter\Cli;
use WeCodeMore\WpStarter\Util\Filesystem;

/**
 * All this class methods receive a "$value" coming from JSON, so we don't have type safety.
 * Methods will check the type and act accordingly.
 *
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
 */
class Validator
{
    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var ComposerFilesystem
     */
    private $filesystem;

    /**
     * @param Paths $paths
     * @param ComposerFilesystem $filesystem
     */
    public function __construct(Paths $paths, ComposerFilesystem $filesystem)
    {
        $this->paths = $paths;
        $this->filesystem = $filesystem;
    }

    /**
     * Validate the "prevent overwrite" setting.
     *
     * It is expected either:
     * - the word "hard", which means always prevent overwrite of anything;
     * - the word "ask", which means ask the user in case of existing file;
     * - a boolean(-like), which enables or not overwrite protection.
     *
     * @param string|bool|array|null $value
     * @return Result
     */
    public function validateOverwrite($value): Result
    {
        if ($value === null) {
            return Result::none();
        }

        if (is_array($value)) {
            return $this->validateGlobPathArray($value);
        }

        return $this->validateBoolOrAsk($value);
    }

    /**
     * Validate an array of custom steps to process.
     *
     * It is expected an array of class names implementing step interface.
     * A single class name is accepted and transparently converted to a one-item array.
     *
     * @param mixed $value
     * @return Result
     */
    public function validateSteps($value): Result
    {
        if (!$value) {
            return Result::none();
        }

        if (!is_array($value)) {
            return Result::errored('Steps config must be an array.');
        }

        $steps = [];
        foreach ($value as $name => $step) {
            if (!is_string($step) || !$this->isValidEntityName($step)) {
                continue;
            }

            $step = ltrim(trim($step), '\\');
            is_string($name) or $name = basename(str_replace('\\', '/', $step));
            $steps[trim($name)] = $step;
        }

        if (!$steps) {
            return Result::errored('No valid step classes provided.');
        }

        return Result::ok($steps);
    }

    /**
     * Validate custom scripts that are callback executed either before or after each step.
     *
     * It is expected an associative array where the keys are strings that starts with either
     * "pre-" or "post-" followed by the name of target step and values are either a callback or
     * an array of callbacks.
     *
     * @param mixed $value
     * @return Result
     */
    public function validateScripts($value): Result
    {
        if (!$value) {
            return Result::ok([]);
        }

        $error = 'Scripts config must an array where keys are script names (start with "pre-" or '
            . '"post-" and values are a single callback or a list of callbacks.';

        if (!is_array($value)) {
            return Result::errored($error);
        }

        $allScripts = [];

        foreach ($value as $name => $scripts) {
            if (!is_string($name) || !preg_match('~^(?:pre|post)\-.+$~i', $name)) {
                return Result::errored($error);
            }

            $name = strtolower($name);

            if (is_string($scripts) && $this->isCallback($scripts)) {
                $allScripts[$name] = [$scripts];
                continue;
            }

            if (!is_array($scripts)) {
                return Result::errored($error);
            }

            $validScripts = array_filter($scripts, [$this, 'isCallback']);
            $validScripts and $allScripts[$name] = array_values($validScripts);
        }

        if (!$allScripts) {
            return Result::errored('No valid scripts provided.');
        }

        return Result::ok($allScripts);
    }

    /**
     * @param mixed $value
     * @return Result
     */
    public function validateDbCheck($value): Result
    {
        if (is_string($value) && (strtolower($value) === DbChecker::HEALTH_CHECK)) {
            return Result::ok(DbChecker::HEALTH_CHECK);
        }

        return $this->validateBool($value);
    }

    /**
     * Validate an array of dropins to process.
     *
     * It is expected an array of path or URLs. Even mixed.
     *
     * @param mixed $value
     * @return Result
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    public function validateDropins($value): Result
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity

        if (!$value) {
            return Result::none();
        }

        if (!is_array($value) && !is_string($value)) {
            return Result::errored('Dropins config must be an array.');
        }

        $dropins = [];
        foreach ((array)$value as $basename => $dropin) {
            $dropin = $this->validateUrlOrPath($dropin)->unwrapOrFallback(null);
            if (($dropin === '') || !is_string($dropin)) {
                continue;
            }
            if (is_numeric($basename)) {
                $basename = filter_var($dropin, FILTER_VALIDATE_URL)
                    ? trim((parse_url($dropin, PHP_URL_PATH) ?: ''), '/') ?: ''
                    : basename($dropin);
            }
            if ($basename !== '') {
                $dropins[$basename] = $dropin;
            }
        }

        return $dropins ? Result::ok($dropins) : Result::errored('No valid dropins provided.');
    }

    /**
     * Validate the operation to apply for "content dev".
     *
     * @param string|bool|null $value
     * @return Result
     */
    public function validateContentDevOperation($value): Result
    {
        return $this->validateOperation(Config::CONTENT_DEV_OPERATION, $value);
    }

    /**
     * Validate the operation to apply for "content dev".
     *
     * @param string|bool|null $value
     * @return Result
     */
    public function validateDropinsOperation($value): Result
    {
        return $this->validateOperation(Config::DROPINS_OPERATION, $value);
    }

    /**
     * Validate the WP CLI commands to execute.
     *
     * It is accepted either:
     * - an array of WP CLI commands as they would be run in the terminal;
     * - a string, that is a path to a PHP or JSON file. The file must return (if PHP) or contain
     *   (if JSON) an array of WP CLI commands as they would be run in the terminal.
     *
     * @param mixed $value
     * @return Result
     */
    public function validateWpCliCommands($value): Result
    {
        if (!$value) {
            return Result::none();
        }

        $error = 'WP CLI commands must be either provided as array of commands, or path to a PHP '
            . 'file returning the array, or path to a JSON file containing the array.';

        if (is_string($value)) {
            /** @var string|null $path */
            $path = $this->validatePath($value)->unwrapOrFallback();

            return $path
                ? $this->validateWpCliCommandsListFile($path)
                : Result::errored($error);
        }

        if (!is_array($value)) {
            return Result::errored($error);
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

        if (!$commands) {
            return Result::errored($error);
        }

        return Result::ok($commands);
    }

    /**
     * Validate a single WP CLI commands to execute.
     *
     * It is expected a string that is the command as it would be run in the terminal.
     *
     * @param mixed $value
     * @return Result
     */
    public function validateWpCliCommand($value): Result
    {
        if (!$value) {
            return Result::none();
        }

        if (!is_string($value)) {
            return Result::errored('A WP CLI command must be a string.');
        }

        if (strpos($value, 'wp ') !== 0) {
            return Result::errored('A WP CLI command must start with "wp ".');
        }

        $value = substr($value, 3) ?: '';
        $hasPath = preg_match('~^(.+)(\-\-path=[^ ]+)(.+)?$~', $value, $matches);
        $hasPath and $value = trim($matches[1] . $matches[3]);

        return Result::ok((string)new StringInput($value));
    }

    /**
     * Validate an array of files to be evaluated by WP CLI commands via `eval_file` command.
     *
     * It is expected an array of file paths, a single path in a string will be transparently
     * converted to a one-item array.
     *
     * @param mixed $value
     * @return Result
     */
    public function validateWpCliFiles($value): Result
    {
        if (!$value) {
            return Result::none();
        }

        is_string($value) and $value = [$value];

        if (!is_array($value)) {
            return Result::errored('Files to be evaluated by WP CLI must be provided as array.');
        }

        $valid = [];
        foreach ($value as $file) {
            if (!is_array($file) && !is_string($file)) {
                continue;
            }

            $data = is_array($file)
                ? Cli\WpCliFileData::fromArray($file)
                : Cli\WpCliFileData::fromPath($file);

            $data->valid() and $valid[] = $data;
        }

        if (!$valid) {
            return Result::errored('No valid file has been provided to be evaluated by WP CLI.');
        }

        return Result::ok($valid);
    }

    /**
     * Validate WP version.
     *
     * Checks that given value represent a valid WP version. It does not check that the version
     * actually exists, just that the value _looks like_ valid version, e.g "4.9.8" or "1.0-alpha".
     * Something like "8.5.1.5" will be considered valid, even if that version does not exist (yet).
     * The returned result in case of success wraps a normalized value in the form "x.x.x".
     *
     * @param mixed $value
     * @return Result
     */
    public function validateWpVersion($value): Result
    {
        if ($value === null) {
            return Result::none();
        }

        if (!is_string($value) && !is_int($value)) {
            return Result::errored('WP version is expected to be a string or an integer.');
        }

        $normalized = WpVersion::normalize((string)$value);
        if (!$normalized) {
            return Result::errored("{$value} does not represent a valid WP version.");
        }

        return Result::ok($normalized);
    }

    /**
     * Generic validator that checks given value is either: a boolean, a valid URL, a valid path,
     * or the word "ask".
     *
     * @param mixed $value
     * @return Result
     */
    public function validateBoolOrAskOrUrlOrPath($value): Result
    {
        $boolOrAskOrUrl = $this->validateBoolOrAskOrUrl($value);
        if ($boolOrAskOrUrl->notEmpty()) {
            return $boolOrAskOrUrl;
        }

        if (!is_string($value)) {
            return Result::errored(
                sprintf(
                    'Given value must be either a valid URL, a valid path, or a boolean, or "%s".',
                    OptionalStep::ASK
                )
            );
        }

        return $this->validatePath($value);
    }

    /**
     * Generic validator that checks given value is either: a boolean, a valid URL, or the word
     * "ask".
     *
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

        return Result::errored(
            sprintf(
                'Given value must be either a valid URL, a boolean or "%s".',
                OptionalStep::ASK
            )
        );
    }

    /**
     * Generic validator that checks given value is either: a boolean or the word "ask".
     *
     * @param mixed $value
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
     * Generic validator that checks given value is either a valid URL or a valid path.
     *
     * @param mixed $value
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
     * Validate given value is a valid path, i.e. existing file or folder.
     *
     * @param mixed $value
     * @return Result
     */
    public function validatePath($value): Result
    {
        $path = $this->validateDirName($value)->unwrapOrFallback();

        if (!$path) {
            return Result::errored('Given value must be the path to an existing file or folder.');
        }

        /** @var string $path */

        if (is_file($path) || is_dir($path)) {
            if (!$this->filesystem->isAbsolutePath($path)) {
                $path = $this->paths->root($this->filesystem->normalizePath("/{$path}"));
            }

            return Result::ok($path);
        }

        $fullpath = $this->paths->root("/{$path}");

        return is_file($fullpath) || is_dir($fullpath)
            ? Result::ok($fullpath)
            : Result::errored('Given value must be the path to an existing file or folder.');
    }

    /**
     * Validate given value is a valid file name.
     *
     * Unfortunately there's no real effective way to check if a string will make a valid file name
     * in PHP. We know some characters are invalid, but some combinations of valid characters are
     * invalid names. E.g. spaces and dots are valid characters, but a string made entirely of dots
     * and spaces is not a valid file name. On top of that PHP has issues with UTF-8 names.
     *
     * This method tries its best to return error in case of clearly wrong file names.
     *
     * @param mixed $value
     * @return Result
     */
    public function validateFileName($value): Result
    {
        if (!is_string($value)) {
            return Result::errored('A file name must be in a string.');
        }

        $normalized = $this->filesystem->normalizePath($value);
        if (!$normalized) {
            return Result::errored("{$value} is not a valid file name.");
        }

        // "prefix" necessary because pathinfo does not work well with entirely non-ASCII names.
        $basename = pathinfo("prefix{$normalized}", PATHINFO_BASENAME);
        if ($basename !== "prefix{$normalized}") {
            return Result::errored("{$value} is not a valid file name.");
        }

        $hasInvalidChars = preg_match(
            '~(\$|\+|\!|\*|\(|\)|,|\{|\}|\||\^|\[|\]|`|"|\>|\<|\#|;|\?|\:|&|\')~',
            $normalized
        );

        if (
            $hasInvalidChars
            || !str_replace([' ', '.', '~', '%', '@', '='], '', $normalized)
            || substr_count($normalized, '..')
        ) {
            return Result::errored("{$value} is not a valid file name.");
        }

        return Result::ok($normalized);
    }

    /**
     * Validate given value is a valid folder name. No check is done if the folder actually exist.
     *
     * This relies on "validateFileName" method, and so its limitations apply here as well.
     *
     * @param mixed $value
     * @return Result
     * @see Validator::validateFileName()
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    public function validateDirName($value): Result
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity
        if (!is_string($value)) {
            return Result::errored('Folder name must be in a string.');
        }

        if (in_array($value, ['.', './', '/'], true)) {
            return Result::ok($value);
        }

        $normalized = $this->filesystem->normalizePath($value);
        if (!$normalized) {
            return Result::errored("{$value} is not a valid folder name.");
        }

        $trimmed = $normalized;
        $startWithSlash = $trimmed[0] === '/';
        $startWithSlash and $trimmed = (substr($trimmed, 1) ?: '');

        $relStartMatch = [];
        while (!$startWithSlash && preg_match('~^\.{1,2}/(.+)?~', $trimmed, $relStartMatch)) {
            $trimmed = $relStartMatch[1];
        }

        if (!substr_count($trimmed, '/')) {
            if (!$this->validateFileName($trimmed)->notEmpty()) {
                return Result::errored("{$value} is not a valid folder name.");
            }

            return Result::ok($normalized);
        }

        // extract a prefix being a protocol://, protocol:, protocol://drive: or simply drive:
        $regex = '{^(?:[0-9a-z]{2,}+:(?://(?:[a-z]:)?)?|[a-z]:)(?:/?(.+))+}i';
        if (($trimmed === $normalized) && preg_match($regex, $trimmed, $driveStartMatch)) {
            $trimmed = $driveStartMatch[1] ?? '';
        }

        foreach (explode('/', $trimmed) as $part) {
            if (!$this->validateFileName($part)->notEmpty()) {
                return Result::errored("{$value} is not a valid folder name.");
            }
        }

        return Result::ok($normalized);
    }

    /**
     * Validate given value is a valid glob path.
     *
     * Similar to validateDirName() and validateFileName() (that are used for the check) the method
     * checks if a path looks like a valid path to be used in glob function in PHP.
     *
     * @param mixed $value
     * @return Result
     * @see Validator::validateFileName()
     * @see Validator::validateDirName()
     */
    public function validateGlobPath($value): Result
    {
        if (!is_string($value) || !$value) {
            return Result::errored("Glob path must be in a non-empty string.");
        }

        if (
            !str_replace(['*', '.', '/', '?'], '', $value)
            && !substr_count($value, '..')
            && !substr_count($value, '//')
        ) {
            return Result::ok($value);
        }

        $path1 = str_replace(['*', '?', '[', ']'], ['aa', 'a', '', ''], $value);

        $valid = substr_count($path1, '/') || substr_count($path1, '\\')
            ? $this->validateDirName($path1)
            : $this->validateFileName($path1);

        if ($valid->notEmpty()) {
            return Result::ok($value);
        }

        return Result::errored("{$value} is not a valid glob path.");
    }

    /**
     * Validate given value to be an array of valid glob paths.
     *
     * Basically applies validateGlobPath() on each item of given array.
     *
     * @param mixed $value
     * @return Result
     * @see Validator::validateGlobPath()
     */
    public function validateGlobPathArray($value): Result
    {
        if (!$value) {
            return Result::none();
        }

        if (!is_array($value)) {
            return Result::errored('Expected an array of glob paths, given value is not an array.');
        }

        $validated = [];
        foreach ($value as $maybePath) {
            $validatedPath = $this->validateGlobPath($maybePath);
            $validatedPath->notEmpty() and $validated[] = $validatedPath->unwrap();
        }

        if (!$validated) {
            return Result::errored(
                'None of the items of provided array represent a valid glob path.'
            );
        }

        return Result::ok($validated);
    }

    /**
     * Validate given value to be a valid URL.
     *
     * @param mixed $value
     * @return Result
     */
    public function validateUrl($value): Result
    {
        if (!$value) {
            return Result::none();
        }

        if (!is_string($value)) {
            return Result::errored('URL must be in a string.');
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return Result::errored("{$value} is not a valid URL.");
        }

        return Result::ok($value);
    }

    /**
     * Validate given value to be a boolean-like value.
     *
     * Beside of actual booleans, strings "true" / "false", "yes" / "no", "on" / "off" and
     * integers 0 / 1 are all valid input and returned result will return a values cast to bool.
     *
     * @param mixed $value
     * @return Result
     */
    public function validateBool($value): Result
    {
        if (in_array($value, [null, ''], true)) {
            return Result::errored('Given value does not represent a boolean.');
        }

        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($bool === null) {
            return Result::errored('Given value does not represent a boolean.');
        }

        return Result::ok($bool);
    }

    /**
     * Validate given value to be either and integer, a float or a string representing them.
     *
     * In case of success the returned result will return a values cast to int.
     *
     * @param int|string|float $value
     * @return Result
     */
    public function validateInt($value): Result
    {
        return is_numeric($value)
            ? Result::ok((int)$value)
            : Result::errored('Given value does not represent an integer.');
    }

    /**
     * Checks given value is an array.
     *
     * Because associative array are accepted, and because "raw" values comes form JSON, instances
     * of `stdClass` are accepted and items extracted from there.
     *
     * @param mixed $value
     * @return Result
     */
    public function validateArray($value): Result
    {
        if ($value instanceof \stdClass) {
            $value = get_object_vars($value);
        }

        return is_array($value)
            ? Result::ok($value)
            : Result::errored('Given value is not, nor can be converted to, an array.');
    }

    /**
     * @param mixed $script
     * @return bool
     */
    private function isCallback($script): bool
    {
        if (!is_callable($script, true)) {
            return false;
        }

        /** @psalm-suppress RedundantCondition */
        if (is_callable($script)) {
            return true;
        }

        /** @var string|array{string|object, string}|object $script */

        if (is_array($script)) {
            return is_string($script[0])
                && $this->isValidEntityName($script[0])
                && $this->isValidEntityName($script[1], false);
        }

        if (!is_string($script)) {
            return false;
        }

        if (preg_match('/^([^:]+)::([^:])+$/', $script, $matches)) {
            return $this->isValidEntityName($matches[1])
                && $this->isValidEntityName($matches[2], false);
        }

        return $this->isValidEntityName($script);
    }

    /**
     * Validate the path of a file containing WP CLI commands.
     *
     * It is expected a string, that is a path to a PHP or JSON file. The file must return (if PHP)
     * or contain (if JSON) an array of WP CLI commands as they would be run in the terminal.
     *
     * @param mixed $value
     * @return Result
     */
    private function validateWpCliCommandsListFile($value): Result
    {
        if ($value === null) {
            return Result::none();
        }

        $error = 'WP CLI commands must be either provided as path to a PHP file returning an array '
            . 'of commands or as path to a JSON file containing the array.';

        $validPath = $this->validatePath($value);
        if (!$validPath->notEmpty()) {
            return Result::errored($error);
        }

        /** @var string $fullpath */
        $fullpath = $validPath->unwrap();
        if (!is_file($fullpath) || !is_readable($fullpath)) {
            return Result::errored("{$error} {$fullpath} is not a file or is not readable.");
        }

        $extension = strtolower((string)pathinfo($fullpath, PATHINFO_EXTENSION));
        $isJson = $extension === 'json';
        if ($extension !== 'php' && !$isJson) {
            return Result::errored($error);
        }

        if ($isJson) {
            $data = @json_decode(file_get_contents($fullpath) ?: '', true);

            return is_array($data) ? $this->validateWpCliCommands($data) : Result::errored($error);
        }

        $provider = function () use ($fullpath, $error): Result {
            try {
                $data = @include $fullpath;
            } catch (\Throwable $throwable) {
                return Result::errored("{$error} " . $throwable->getMessage());
            }

            return is_array($data)
                ? $this->validateWpCliCommands($data)
                : Result::errored($error);
        };

        return Result::promise($provider);
    }

    /**
     * @param string $value
     * @param bool $namespace
     * @return bool
     */
    private function isValidEntityName(string $value, bool $namespace = true): bool
    {
        $parts = $namespace ? explode('\\', ltrim($value, '\\')) : [$value];
        foreach ($parts as $part) {
            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate the operation to apply for "content dev".
     *
     * WP Starter allows to have plugins, themes and mu-plugins in the same folder of the project
     * itself. For WordPress to be able to recognize those, it is needed they are placed in the
     * wp-content folder, which will also contains 3rd party plugins, themes and mu-plugins pulled
     * via Composer. To keep things separated, and easily managed via Git, WP Starter allows
     * "1st hand" content to be placed in a separate folder of the project and then either
     * symlinked or copied into wp-content folder.
     *
     * This setting tells WP starter what to do: symlink (default) or copy the files.
     * It is accepted:
     * - the word "auto"
     * - the word "symlink"
     * - the word "copy"
     * - the word "none", which means do nothing
     * - boolean true, which means default operation, i.e. "auto"
     * - boolean false, which means do nothing
     *
     * @param string $label
     * @param mixed $value
     * @return Result
     */
    private function validateOperation(string $label, $value): Result
    {
        if ($value === null) {
            return Result::none();
        }

        if ($value === OptionalStep::ASK) {
            return Result::ok($value);
        }

        is_string($value) and $value = trim(strtolower($value));
        if (in_array($value, Filesystem::OPERATIONS, true)) {
            return Result::ok($value);
        }

        $bool = $this->validateBool($value);
        if (!$bool->either(true, false)) {
            return Result::errored(
                sprintf(
                    '"%s" configuration must be either: "%s", "%s", true or false.',
                    $label,
                    implode('", "', Filesystem::OPERATIONS),
                    OptionalStep::ASK
                )
            );
        }

        return $bool->is(true)
            ? Result::ok(Filesystem::OP_AUTO)
            : Result::ok(Filesystem::OP_NONE);
    }
}
