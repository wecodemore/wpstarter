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
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\UrlDownloader;

/**
 * Steps that stores .env.example in root folder.
 *
 * WP Starter requires a .env file to make a WordPress installation usable.
 * This step place a .env.example files in project root which serves as example to build the actual
 * .env file and includes all the possible configuration values that WordPress uses plus a few
 * that are specific to WP Starter.
 */
final class EnvExampleStep implements FileCreationStep, OptionalStep, ConditionalStep
{
    public const NAME = 'envexample';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var UrlDownloader
     */
    private $urlDownloader;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var string
     */
    private $reason = '';

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->config = $locator->config();
        $this->filesystem = $locator->filesystem();
        $this->urlDownloader = $locator->urlDownloader();
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
        if ($config[Config::ENV_EXAMPLE]->is(false)) {
            $this->reason = sprintf('disabled via "%s" configuration', Config::ENV_EXAMPLE);

            return false;
        }

        /** @var string $envFileName */
        $envFileName = $config[Config::ENV_FILE]->unwrapOrFallback('.env');
        /** @var string $envDir */
        $envDir = $config[Config::ENV_DIR]->unwrapOrFallback('');
        $envFile = $this->filesystem->normalizePath("{$envDir}/{$envFileName}");

        if (is_file($paths->root($envFile))) {
            $this->reason = 'environment file already exists';

            return false;
        }

        $this->reason = '';

        return true;
    }

    /**
     * @param Paths $paths
     * @return string
     */
    public function targetPath(Paths $paths): string
    {
        /** @var string $envDir */
        $envDir = $this->config[Config::ENV_DIR]->unwrapOrFallback('');
        if ($envDir === '') {
            $envDir = $paths->root('');
        }

        return $this->filesystem->normalizePath("{$envDir}/.env.example");
    }

    /**
     * @param Config $config
     * @param Io $io
     * @return bool
     */
    public function askConfirm(Config $config, Io $io): bool
    {
        if ($config[Config::ENV_EXAMPLE]->is(OptionalStep::ASK)) {
            $lines = [
                'Do you want to save .env.example file to',
                'your project folder?',
            ];

            return $io->askConfirm($lines, true);
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
        /** @var string|bool $source */
        $source = $this->config[Config::ENV_EXAMPLE]->unwrapOrFallback(false);
        if (!$source) {
            return Step::NONE;
        }

        $destination = $this->targetPath($paths);

        if (is_string($source) && filter_var($source, FILTER_VALIDATE_URL)) {
            return $this->download($source, $destination);
        }

        $isAsk = $source === OptionalStep::ASK;

        if (!$isAsk && is_string($source)) {
            $realpath = realpath($source);
            if (!$realpath) {
                $this->error = "{$source} is not a valid valid relative path to env-example file.";

                return Step::ERROR;
            }

            return $this->copy($paths, $destination, $realpath);
        }

        return $this->copy($paths, $destination);
    }

    /**
     * Download a remote .env.example in root folder.
     *
     * @param non-empty-string $url
     * @param string $destination
     * @return int
     */
    private function download(string $url, string $destination): int
    {
        if (!$this->urlDownloader->save($url, $destination)) {
            $error = $this->urlDownloader->error();
            $this->error = "Error downloading and saving {$url}: {$error}";

            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * Copy a .env.example in root folder.
     *
     * @param  Paths $paths
     * @param  string $destination
     * @param  string|null $source
     * @return int
     */
    private function copy(Paths $paths, string $destination, ?string $source = null): int
    {
        if ($source === null) {
            $source = $paths->template('.env.example');
        }

        if ($this->filesystem->copyFile($source, $destination)) {
            return self::SUCCESS;
        }

        $this->error = 'Error on copy default .env.example in root folder.';

        return self::ERROR;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function skipped(): string
    {
        return 'env.example copy skipped.';
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return '<comment>env.example</comment> saved successfully.';
    }

    /**
     * @return string
     */
    public function conditionsNotMet(): string
    {
        return $this->reason;
    }
}
