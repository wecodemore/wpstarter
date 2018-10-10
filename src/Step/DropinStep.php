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
 * Step for a single dropin processing.
 */
final class DropinStep implements FileCreationStepInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $actionSource;

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

        $this->name = filter_var($name, FILTER_SANITIZE_URL);
        $this->url = $url;
        $this->io = $io;
        $this->urlDownloader = $urlDownloader;
        $this->overwrite = $overwrite;
    }

    /**
     * @inheritdoc
     */
    public function name(): string
    {
        return 'dropin-' . pathinfo($this->name, PATHINFO_FILENAME);
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        $this->actionSource = $this->action($this->url, $paths);

        if (empty($this->actionSource[0])) {
            $this->io->writeError("{$this->url} is not a valid url nor a valid path.");

            return false;
        }

        return true;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $dest = $this->targetPath($paths);
        if (!$this->overwrite->shouldOverwite($dest)) {
            $this->io->writeComment("  - {$this->name} skipped because existing.");

            return self::NONE;
        }

        return $this->actionSource[0] === 'download'
            ? $this->download($this->actionSource[1], $dest)
            : $this->copy($this->actionSource[1], $dest);
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
     * @param string $dest
     * @return int
     */
    private function download(string $url, string $dest): int
    {
        $name = basename($dest);
        if (!$this->urlDownloader->save($url, $dest)) {
            $error = $this->urlDownloader->error();
            $this->error .= "\nIt was not possible to download and save {$name}: {$error}";

            return self::ERROR;
        }

        $this->success .= "\n<comment>{$name}</comment> downloaded and saved successfully.";

        return self::SUCCESS;
    }

    /**
     * Copy dropin file from given source path and save it in wp-content folder.
     *
     * @param string $source
     * @param string $dest
     * @return int
     */
    private function copy(string $source, string $dest): int
    {
        $sourceBase = basename($source);
        $name = basename($dest);
        try {
            $copied = copy($source, $dest);
            $copied
                ? $this->success .= "\n<comment>{$name}</comment> copied successfully."
                : $this->error .= "\nImpossible to copy {$sourceBase} to {$name}.";

            return $copied ? self::SUCCESS : self::ERROR;
        } catch (\Throwable $exception) {
            $this->error .= "\nImpossible to copy {$sourceBase} to {$name}.";

            return self::ERROR;
        }
    }

    /**
     * Check if a string is a valid relative path or an url.
     *
     * @param  string $url
     * @param  Paths $paths
     * @return array
     */
    private function action(string $url, Paths $paths): array
    {
        $realpath = realpath($paths->root($url));

        if ($realpath && is_file($realpath)) {
            return ['copy', $realpath];
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return ['download', $url];
        }

        return [null, null];
    }
}
