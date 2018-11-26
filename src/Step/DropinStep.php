<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\OverwriteHelper;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\UrlDownloader;

/**
 * `DropinsStep` class make use of this class to process a single dropin.
 *
 * This step is not run directly from WP Starter, but instantiated and used by `DropinsStep` only.
 */
final class DropinStep implements FileCreationStepInterface
{
    const ACTION_COPY = 'copy';
    const ACTION_DOWNLOAD = 'download';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $url;

    /**
     * @var \stdClass
     */
    private $actionAndSource;

    /**
     * @var \WeCodeMore\WpStarter\Util\Io
     */
    private $io;

    /**
     * @var UrlDownloader
     */
    private $urlDownloader;

    /**
     * @var \WeCodeMore\WpStarter\Util\OverwriteHelper
     */
    private $overwrite;

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
     * @param \WeCodeMore\WpStarter\Util\Io $io
     * @param UrlDownloader $urlDownloader
     * @param \WeCodeMore\WpStarter\Util\OverwriteHelper $overwrite
     */
    public function __construct(
        string $name,
        string $url,
        Io $io,
        UrlDownloader $urlDownloader,
        OverwriteHelper $overwrite
    ) {

        $this->name = basename($name);
        $this->url = $url;
        $this->io = $io;
        $this->urlDownloader = $urlDownloader;
        $this->overwrite = $overwrite;
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
            $this->io->writeError("{$this->url} is not a valid URL nor a valid path.");

            return false;
        }

        $this->actionAndSource = (object)compact('action', 'source');

        return true;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        if (!$this->actionAndSource) {
            return self::NONE;
        }

        $destination = $this->targetPath($paths);
        if (!$this->overwrite->shouldOverwrite($destination)) {
            $this->io->writeComment("  - {$this->name} skipped because existing.");

            return self::NONE;
        }

        return $this->actionAndSource->action === self::ACTION_DOWNLOAD
            ? $this->download($this->actionAndSource->source, $destination)
            : $this->copy($this->actionAndSource->source, $destination);
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
     * @param string $url
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
     * @param string $destination
     * @return int
     */
    private function copy(string $source, string $destination): int
    {
        $sourceBase = basename($source);
        $name = basename($destination);
        try {
            $copied = copy($source, $destination);
            $copied
                ? $this->success .= "<comment>{$name}</comment> copied successfully."
                : $this->error .= "Impossible to copy {$sourceBase} to {$name}.";

            return $copied ? self::SUCCESS : self::ERROR;
        } catch (\Throwable $exception) {
            $this->error .= "Impossible to copy {$sourceBase} to {$name}.";

            return self::ERROR;
        }
    }

    /**
     * Check if a string is a valid relative path or an url.
     *
     * @return array
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
}
