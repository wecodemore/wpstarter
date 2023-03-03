<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\OverwriteHelper;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Step to process dropins.
 *
 * Even if dropins are supported by Composer installers, often the only way to place them *directly*
 * in WP content folder (where WordPress recognize them), is to put them there before Composer is
 * even ran, so basically make them part of the project, which makes hard to reuse them.
 * WP Starter, via this step, allows taking dropins from an arbitrary source (local paths or URLs)
 * and put in WP content folder. Moreover, if dropins are placed in subfolders of wp-content by
 * Composer (thanks to Composer installers) then this step moves them up to content folder.
 */
final class DropinsStep implements Step
{
    public const NAME = 'dropins';

    public const DROPINS = [
        'advanced-cache.php',
        'db.php',
        'db-error.php',
        'install.php',
        'maintenance.php',
        'object-cache.php',
        'sunrise.php',
        'blog-deleted.php',
        'blog-inactive.php',
        'blog-suspended.php',
    ];

    /**
     * @var \WeCodeMore\WpStarter\Io\Io
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Util\PackageFinder
     */
    private $packageFinder;

    /**
     * @var \WeCodeMore\WpStarter\Util\UrlDownloader
     */
    private $urlDownloader;

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var OverwriteHelper
     */
    private $overwriteHelper;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var string
     */
    private $success = '';

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->io = $locator->io();
        $this->packageFinder = $locator->packageFinder();
        $this->urlDownloader = $locator->urlDownloader();
        $this->filesystem = $locator->filesystem();
        $this->overwriteHelper = $locator->overwriteHelper();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        return $config[Config::DROPINS]->notEmpty() && $paths->wpContent();
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $fromPackages = $this->publishDropinsFromPackages($paths);
        $custom = $this->publishCustomDropins($config, $paths);

        $result = 0;

        if ((($fromPackages | $custom) & Step::SUCCESS) === Step::SUCCESS) {
            $result |= Step::SUCCESS;
        }

        if ((($fromPackages | $custom) & Step::ERROR) === Step::ERROR) {
            $result |= Step::ERROR;
        }

        return $result ?: Step::NONE;
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
     * @return int
     */
    private function publishDropinsFromPackages(Paths $paths): int
    {
        $installed = $this->packageFinder->findByType('wordpress-dropin');
        if (!$installed) {
            return Step::NONE;
        }

        $all = 0;
        $done = 0;
        foreach ($installed as $package) {
            $dir = $this->packageFinder->findPathOf($package);
            if (is_dir($dir)) {
                $all++;
                $this->publishDropinsFromInstalledPath($dir, $paths) and $done++;
            }
        }

        if ($all === $done) {
            return Step::SUCCESS;
        }

        if ($all && !$done) {
            return Step::ERROR;
        }

        return Step::SUCCESS | Step::ERROR;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    private function publishCustomDropins(Config $config, Paths $paths): int
    {
        /** @var array<string, string> $customDropins */
        $customDropins = $config[Config::DROPINS]->unwrapOrFallback([]);
        if (!$customDropins) {
            return Step::NONE;
        }

        foreach ($customDropins as $basename => $url) {
            $this->runDropinStep($basename, $url, $config, $paths);
        }

        if (!$this->error) {
            return Step::SUCCESS;
        }

        if (!$this->success) {
            return Step::ERROR;
        }

        return Step::SUCCESS | Step::ERROR;
    }

    /**
     * @param string $srcDir
     * @param Paths $paths
     * @return bool
     */
    private function publishDropinsFromInstalledPath(string $srcDir, Paths $paths): bool
    {
        $all = 0;
        $done = 0;
        $target = $paths->wpContent();
        foreach (self::DROPINS as $file) {
            if (!is_file("{$srcDir}/{$file}")) {
                continue;
            }

            if (
                file_exists("{$target}/{$file}")
                && !$this->overwriteHelper->shouldOverwrite("{$target}/{$file}")
            ) {
                continue;
            }

            $all++;
            if (!$this->filesystem->symlinkOrCopy("{$srcDir}/{$file}", "{$target}/{$file}")) {
                $this->error .= "Error copying {$file} dropin to {$target}\n";
                continue;
            }

            $done++;
        }

        $this->filesystem->removeRealDir($srcDir);

        return $all === $done;
    }

    /**
     * @param string $basename
     * @param string $url
     * @param Config $config
     * @param Paths $paths
     * @return void
     */
    private function runDropinStep(string $basename, string $url, Config $config, Paths $paths)
    {
        $step = new DropinStep(
            $basename,
            $url,
            $this->io,
            $this->urlDownloader,
            $this->overwriteHelper,
            $this->filesystem
        );

        if (!$step->allowed($config, $paths)) {
            return;
        }

        $result = $step->run($config, $paths);
        switch ($result) {
            case Step::SUCCESS:
                $this->success .= $step->success() . "\n";
                break;
            case Step::ERROR:
                $this->error .= $step->error() . "\n";
                break;
        }
    }
}
