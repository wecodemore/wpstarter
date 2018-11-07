<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Env\WordPressEnvBridge;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Step that generates and saves wp-config.php in webroot.
 *
 * This could be seen as the "main" WP Starter step, because it allows WordPress to work by creating
 * a wp-config.php file that includes all the necessary configuration.
 */
final class WpConfigStep implements FileCreationStepInterface, BlockingStep
{
    const NAME = 'build-wp-config';

    /**
     * @var \WeCodeMore\WpStarter\Util\Io
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Util\FileContentBuilder
     */
    private $builder;

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var \Composer\Util\Filesystem
     */
    private $composerFilesystem;

    /**
     * @var \WeCodeMore\WpStarter\Util\UrlDownloader
     */
    private $urlDownloader;

    /**
     * @var \WeCodeMore\WpStarter\Util\Salter
     */
    private $salter;

    /**
     * @var \WeCodeMore\WpStarter\Config\Config
     */
    private $config;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->io = $locator->io();
        $this->builder = $locator->fileContentBuilder();
        $this->filesystem = $locator->filesystem();
        $this->composerFilesystem = $locator->composerFilesystem();
        $this->urlDownloader = $locator->urlDownloader();
        $this->salter = $locator->salter();
        $this->config = $locator->config();
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
        return true;
    }

    /**
     * @param Paths $paths
     * @return string
     */
    public function targetPath(Paths $paths): string
    {
        return $paths->wpParent('wp-config.php');
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $register = $config[Config::REGISTER_THEME_FOLDER]->unwrapOrFallback(false);
        ($register === OptionalStep::ASK) and $register = $this->askForRegister();

        $from = $this->composerFilesystem->normalizePath($paths->wpParent());

        $cachedEnv = $from . WordPressEnvBridge::CACHE_DUMP_FILE;
        if (file_exists($cachedEnv)) {
            $this->io->writeIfVerbose("Deleting env cache file '{$cachedEnv}'.");
            unlink($cachedEnv);
        }

        $envDir = $config[Config::ENV_DIR]->unwrapOrFallback($paths->root());

        $earlyHook = $config[Config::EARLY_HOOKS_FILE]->unwrapOrFallback('');
        $earlyHook and $earlyHook = $this->relPath("{$from}/index.php", $earlyHook, false);

        $envBootstrapDir = $config[Config::ENV_BOOTSTRAP_DIR]->unwrapOrFallback('');
        if ($envBootstrapDir) {
            $envBootstrapDir = $this->relPath($from, $paths->root($envBootstrapDir));
        }

        $wpRelDir = $this->relPath($from, $paths->wp());
        $contentRelDir = $this->relPath($from, $paths->wpContent());

        $vars = [
            'AUTOLOAD_PATH' => $this->relPath("{$from}/index.php", $paths->vendor('autoload.php'), false),
            'ENV_REL_PATH' => $this->relPath($from, $envDir),
            'ENV_FILE_NAME' => $config[Config::ENV_FILE]->unwrapOrFallback('.env'),
            'ENV_BOOTSTRAP_DIR' => $envBootstrapDir ? "{$envBootstrapDir}/" : '',
            'WP_INSTALL_PATH' => $wpRelDir,
            'WP_CONTENT_PATH' => $contentRelDir,
            'REGISTER_THEME_DIR' => $register ? 'true' : 'false',
            'WP_SITEURL_RELATIVE' => $this->stripDot($wpRelDir),
            'WP_CONTENT_URL_RELATIVE' => $this->stripDot($contentRelDir),
            'EARLY_HOOKS_FILE' => $earlyHook,
        ];

        $built = $this->builder->build(
            $paths,
            'wp-config.php',
            array_merge($vars, $this->salter->keys())
        );

        if (!$this->filesystem->save($built, $this->targetPath($paths))) {
            $this->error = 'Error on create wp-config.php.';

            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * @inheritdoc
     */
    public function error(): string
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function success(): string
    {
        return '<comment>wp-config.php</comment> saved successfully.';
    }

    /**
     * @return bool
     */
    private function askForRegister(): bool
    {
        $lines = [
            'Do you want to register WordPress package wp-content folder '
            . 'as additional theme folder for your project?',
        ];

        return $this->io->askConfirm($lines, true);
    }

    /**
     * @param string $from
     * @param string $to
     * @param bool $bothDirs
     * @return string
     */
    private function relPath(string $from, string $to, bool $bothDirs = true): string
    {
        $path = $this->composerFilesystem->normalizePath(
            $this->composerFilesystem->findShortestPath($from, $to, $bothDirs)
        );

        return "/{$path}";
    }

    /**
     * @param string $path
     * @return string
     */
    private function stripDot(string $path): string
    {
        $path = ltrim($path, '/');
        strpos($path, './') === 0 and $path = substr($path, 2);
        strpos($path, '../') === 0 and $path = dirname($path);

        return $path;
    }
}
