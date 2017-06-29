<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use Composer\Util\Filesystem as FilesystemUtil;
use WeCodeMore\WpStarter\Utils\Config;
use WeCodeMore\WpStarter\Utils\Filesystem;
use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\FileBuilder;
use WeCodeMore\WpStarter\Utils\Paths;
use WeCodeMore\WpStarter\Utils\Salter;

/**
 * Step that generates and saves wp-config.php.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class WPConfigStep implements FileCreationStepInterface, BlockingStepInterface
{
    const NAME = 'build-wpconfig';

    /**
     * @var \WeCodeMore\WpStarter\Utils\IO
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Utils\FileBuilder
     */
    private $builder;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Filesystem
     */
    private $filesystem;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Salter
     */
    private $salter;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Config
     */
    private $config;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @param \WeCodeMore\WpStarter\Utils\IO $io
     * @param \WeCodeMore\WpStarter\Utils\Filesystem $filesystem
     * @param \WeCodeMore\WpStarter\Utils\FileBuilder $builder
     * @param \WeCodeMore\WpStarter\Utils\Salter|null $salter
     */
    public function __construct(
        IO $io,
        Filesystem $filesystem,
        FileBuilder $builder,
        Salter $salter = null
    ) {
        $this->io = $io;
        $this->builder = $builder;
        $this->filesystem = $filesystem;
        $this->salter = $salter ?: new Salter();
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return self::NAME;
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, Paths $paths)
    {
        $this->config = $config;

        return true;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function targetPath(Paths $paths)
    {
        return $paths->wp_parent('wp-config.php');
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function run(Paths $paths, $verbosity)
    {
        $register = $this->config[Config::REGISTER_THEME_FOLDER];
        $register === 'ask' and $this->askForRegister();

        $util = $this->filesystem->composerFilesystem();

        $from = $util->normalizePath($paths->wp_parent());

        $relPath = function ($to, $bothDirs = true) use ($from, $paths, $util) {
            return $util->findShortestPath($from, $to, $bothDirs);
        };

        $relUrl = function ($path) use ($from, $paths) {

            strpos($path, './') === 0 and $subdir = substr($path, 2);

            return $path;
        };

        $earlyHookFile = Config::EARLY_HOOKS_FILE ? $paths->root(Config::EARLY_HOOKS_FILE) : '';
        $earlyHookFile and $earlyHookFile = realpath($earlyHookFile);

        $vars = [
            'AUTOLOAD_PATH'      => $relPath($paths->vendor('autoload.php'), false),
            'ENV_REL_PATH'       => $relPath($paths->root()),
            'WP_INSTALL_PATH'    => $relPath($paths->wp()),
            'WP_CONTENT_PATH'    => $relPath($paths->wp_content()),
            'REGISTER_THEME_DIR' => $register ? 'true' : 'false',
            'ENV_FILE_NAME'      => $this->config[Config::ENV_FILE],
            'WP_SITEURL'         => $relUrl($paths->relative(Paths::WP)),
            'WP_CONTENT_URL'     => $relUrl($paths->relative(Paths::WP_CONTENT)),
            'EARLY_HOOKS_FILE'   => $earlyHookFile ? $relPath($earlyHookFile) : '',
        ];

        $build = $this->builder->build(
            $paths,
            'wp-config.example',
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
    public function error()
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        return '<comment>wp-config.php</comment> saved successfully.';
    }

    /**
     * @return bool
     */
    private function askForRegister()
    {
        $lines = [
            'Do you want to register WordPress package wp-content folder',
            'as additional theme folder for your project?',
        ];

        return $this->io->confirm($lines, true);
    }

}
