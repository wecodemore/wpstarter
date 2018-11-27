<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
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
     * @var \WeCodeMore\WpStarter\Io\Io
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
     * @var \WeCodeMore\WpStarter\Util\Salter
     */
    private $salter;

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->io = $locator->io();
        $this->builder = $locator->fileContentBuilder();
        $this->filesystem = $locator->filesystem();
        $this->composerFilesystem = $locator->composerFilesystem();
        $this->salter = $locator->salter();
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
        $from = $this->composerFilesystem->normalizePath($paths->wpParent());

        $autoload = $paths->vendor('autoload.php');

        $cacheEnv = $config[Config::CACHE_ENV]->unwrapOrFallback(true);

        $earlyHook = $config[Config::EARLY_HOOKS_FILE]->unwrapOrFallback('');
        $earlyHook and $earlyHook = $this->relPath("{$from}/index.php", $earlyHook, false);

        $envBootstrapDir = $config[Config::ENV_BOOTSTRAP_DIR]->unwrapOrFallback('');
        if ($envBootstrapDir) {
            $envBootstrapDir = $this->relPath($from, $paths->root($envBootstrapDir));
        }

        $envDir = $config[Config::ENV_DIR]->unwrapOrFallback($paths->root());

        $register = $config[Config::REGISTER_THEME_FOLDER]->unwrapOrFallback(false);
        ($register === OptionalStep::ASK) and $register = $this->askForRegister();

        $contentRelDir = $this->relPath($from, $paths->wpContent());

        $wpRelDir = $this->relPath($from, $paths->wp());

        $vars = [
            'AUTOLOAD_PATH' => $this->relPath("{$from}/index.php", $autoload, false),
            'CACHE_ENV' => $cacheEnv ? '1' : '',
            'EARLY_HOOKS_FILE' => $earlyHook,
            'ENV_BOOTSTRAP_DIR' => $envBootstrapDir ? "{$envBootstrapDir}/" : '',
            'ENV_FILE_NAME' => $config[Config::ENV_FILE]->unwrapOrFallback('.env'),
            'ENV_REL_PATH' => $this->relPath($from, $envDir),
            'REGISTER_THEME_DIR' => $register ? 'true' : 'false',
            'WP_CONTENT_PATH' => $contentRelDir,
            'WP_CONTENT_URL_RELATIVE' => $this->stripDot($contentRelDir),
            'WP_INSTALL_PATH' => $wpRelDir,
            'WP_SITEURL_RELATIVE' => $this->stripDot($wpRelDir),
        ];

        $built = $this->builder->build(
            $paths,
            'wp-config.php',
            array_merge($vars, $this->salter->keys())
        );

        if (!$this->filesystem->save($built, $this->targetPath($paths))) {
            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return 'Creation of wp-config.php failed.';
    }

    /**
     * @return string
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
