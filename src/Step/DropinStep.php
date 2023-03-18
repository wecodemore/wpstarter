<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Filesystem;
use WeCodeMore\WpStarter\Util\OverwriteHelper;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\UrlDownloader;

/**
 * `DropinsStep` class make use of this class to process a single dropin.
 *
 * This step is not run directly from WP Starter, but instantiated and used by `DropinsStep` only.
 */
final class DropinStep implements FileCreationStep
{
    public const ACTION_COPY = 'copy';
    public const ACTION_DOWNLOAD = 'download';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var UrlDownloader
     */
    private $urlDownloader;

    /**
     * @var OverwriteHelper
     */
    private $overwrite;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array{string, string}|array{null, null}
     */
    private $actionAndSource = [null, null];

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var string
     */
    private $success = '';

    /**
     * @param string $name
     * @param string $url
     * @param string $operation
     * @param Io $io
     * @param UrlDownloader $urlDownloader
     * @param OverwriteHelper $overwrite
     * @param Filesystem $filesystem
     */
    public function __construct(
        string $name,
        string $url,
        string $operation,
        Io $io,
        UrlDownloader $urlDownloader,
        OverwriteHelper $overwrite,
        Filesystem $filesystem
    ) {

        $this->name = basename($name);
        $this->url = $url;
        $this->operation = $operation;
        $this->io = $io;
        $this->urlDownloader = $urlDownloader;
        $this->overwrite = $overwrite;
        $this->filesystem = $filesystem;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'dropin-' . pathinfo($this->name, PATHINFO_FILENAME);
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        list($action, $source) = $this->determineActionAndSource();

        if (!$action || !$source) {
            $this->io->writeErrorBlock("{$this->url} is not a valid URL nor a valid path.");

            return false;
        }

        $this->actionAndSource = [$action, $source];

        return true;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        list($action, $source) = $this->actionAndSource;

        $isDownload = $action === self::ACTION_DOWNLOAD;
        if ((!$isDownload && ($action !== self::ACTION_COPY)) || !$source) {
            return self::NONE;
        }

        $destination = $this->targetPath($paths);
        if (!$this->overwrite->shouldOverwrite($destination)) {
            $this->io->writeComment("  - {$this->name} skipped because existing.");

            return self::NONE;
        }

        return $isDownload
            ? $this->download($source, $destination)
            : $this->symlinkOrCopy($source, $destination);
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return trim($this->error);
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return trim($this->success);
    }

    /**
     * @param Paths $paths
     * @return string
     */
    public function targetPath(Paths $paths): string
    {
        return $paths->wpContent($this->name);
    }

    /**
     * Download dropin file from given url and save it to in wp-content folder.
     *
     * @param non-empty-string $url
     * @param string $destination
     * @return int
     */
    private function download(string $url, string $destination): int
    {
        $name = basename($destination);
        if (!$this->urlDownloader->save($url, $destination)) {
            $error = $this->urlDownloader->error();
            $this->error .= "It was not possible to download and save {$name}: {$error}";

            return self::ERROR;
        }

        $this->success .= "<comment>{$name}</comment> downloaded and saved successfully.";

        return self::SUCCESS;
    }

    /**
     * Copy dropin file from given source path and save it in wp-content folder.
     *
     * @param string $source
     * @param string $target
     * @return int
     */
    private function symlinkOrCopy(string $source, string $target): int
    {
        $name = basename($target);

        try {
            $this->filesystem->unlinkOrRemove($target);
            $copied = $this->filesystem->symlinkOrCopyOperation($source, $target, $this->operation);

            $copied
                ? $this->success .= $this->describeSuccess($name)
                : $this->error .= $this->describeError($name, $source);

            return $copied ? self::SUCCESS : self::ERROR;
        } catch (\Throwable $exception) {
            $this->error .= $this->describeError($name, $source);

            return self::ERROR;
        }
    }

    /**
     * Check if a string is a valid relative path or an url.
     *
     * @return array{string, string}|array{null, null}
     */
    private function determineActionAndSource(): array
    {
        if (filter_var($this->url, FILTER_VALIDATE_URL)) {
            return [self::ACTION_DOWNLOAD, $this->url];
        }

        $realpath = realpath($this->url);

        if ($realpath && is_file($realpath)) {
            return [self::ACTION_COPY, $realpath];
        }

        return [null, null];
    }

    /**
     * @param string $name
     * @return string
     */
    private function describeSuccess(string $name): string
    {
        if ($this->operation === Filesystem::OP_COPY) {
            return "<comment>{$name}</comment> dropin copied successfully.";
        }

        if ($this->operation === Filesystem::OP_SYMLINK) {
            return "<comment>{$name}</comment> dropin symlinked successfully.";
        }

        return "<comment>{$name}</comment> dropin published successfully.";
    }

    /**
     * @param string $name
     * @param string $source
     * @return string
     */
    private function describeError(string $name, string $source): string
    {
        if ($this->operation === Filesystem::OP_COPY) {
            return "Failed copying {$source} as {$name} dropin.";
        }

        if ($this->operation === Filesystem::OP_SYMLINK) {
            return "Failed symlinking {$source} as {$name} dropin.";
        }

        return "Both symlink and copy from {$source} to {$name} dropin failed.";
    }
}
