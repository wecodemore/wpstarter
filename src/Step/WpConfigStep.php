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
 * Step that generates and saves wp-config.php.
 */
final class WpConfigStep implements FileCreationStepInterface, BlockingStep
{
    const NAME = 'build-wpconfig';

    /**
     * @var \WeCodeMore\WpStarter\Util\Io
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Util\FileBuilder
     */
    private $builder;

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

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
        $this->builder = $locator->fileBuilder();
        $this->filesystem = $locator->filesystem();
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
        $register = $this->config[Config::REGISTER_THEME_FOLDER]->unwrapOrFallback();
        $register === OptionalStep::ASK and $this->askForRegister();

        $from = $this->filesystem->composerFilesystem()->normalizePath($paths->wpParent());
        $earlyHookFile = $config[Config::EARLY_HOOKS_FILE]->unwrapOrFallback();

        $vars = [
            'AUTOLOAD_PATH' => $this->relPath($from, $paths->vendor('autoload.php'), false),
            'ENV_REL_PATH' => $this->relPath($from, $paths->root()),
            'ENV_FILE_NAME' => $this->config[Config::ENV_FILE]->unwrapOrFallback('.env'),
            'WP_INSTALL_PATH' => $this->relPath($from, $paths->wp()),
            'WP_CONTENT_PATH' => $this->relPath($from, $paths->wpContent()),
            'REGISTER_THEME_DIR' => $register ? 'true' : 'false',
            'WP_SITEURL' => $this->stripDot($paths->relative(Paths::WP)),
            'WP_CONTENT_URL' => $this->stripDot($paths->relative(Paths::WP_CONTENT)),
            'EARLY_HOOKS_FILE' => $earlyHookFile ? $this->relPath($from, $earlyHookFile) : '',
        ];

        $build = $this->builder->build(
            $paths,
            'wp-config.php',
            array_merge($vars, $this->salter->keys())
        );

        if (!$this->filesystem->save($build, $this->targetPath($paths))) {
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
            'Do you want to register WordPress package wp-content folder',
            'as additional theme folder for your project?',
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
        return $this->filesystem->composerFilesystem()->findShortestPath($from, $to, $bothDirs);
    }

    /**
     * @param string $path
     * @return string
     */
    private function stripDot(string $path): string
    {
        strpos($path, './') === 0 and $subdir = substr($path, 2);
        strpos($path, '../') === 0 and $subdir = dirname($path);

        return $path;
    }
}
