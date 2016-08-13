<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup\Steps;

use Composer\Util\Filesystem as FilesystemUtil;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\Filesystem;
use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\FileBuilder;
use WCM\WPStarter\Setup\Salter;

/**
 * Step that generates and saves wp-config.php.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class WPConfigStep implements FileCreationStepInterface, BlockingStepInterface
{
    /**
     * @var \Composer\Util\Filesystem
     */
    private $filesystemUtil;

    /**
     * @var \WCM\WPStarter\Setup\IO
     */
    private $io;

    /**
     * @var \WCM\WPStarter\Setup\FileBuilder
     */
    private $builder;

    /**
     * @var \WCM\WPStarter\Setup\Filesystem
     */
    private $filesystem;

    /**
     * @var \WCM\WPStarter\Setup\Salter
     */
    private $salter;

    /**
     * @var \WCM\WPStarter\Setup\Config
     */
    private $config;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @param \WCM\WPStarter\Setup\IO $io
     * @param \WCM\WPStarter\Setup\Filesystem $filesystem
     * @param \WCM\WPStarter\Setup\FileBuilder $filebuilder
     * @return static
     */
    public static function instance(
        IO $io,
        Filesystem $filesystem,
        FileBuilder $filebuilder
    ) {
        return new static($io, $filebuilder, $filesystem, new Salter());
    }

    /**
     * @param \WCM\WPStarter\Setup\IO $io
     * @param \WCM\WPStarter\Setup\FileBuilder $builder
     * @param \WCM\WPStarter\Setup\Filesystem $filesystem
     * @param \WCM\WPStarter\Setup\Salter|null $salter
     */
    public function __construct(
        IO $io,
        FileBuilder $builder,
        Filesystem $filesystem,
        Salter $salter
    ) {
        $this->io = $io;
        $this->builder = $builder;
        $this->filesystem = $filesystem;
        $this->salter = $salter;
        $this->filesystemUtil = new FilesystemUtil();
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return 'build-wpconfig';
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, \ArrayAccess $paths)
    {
        $this->config = $config;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function targetPath(\ArrayAccess $paths)
    {
        return $this->filesystemUtil->normalizePath(
            "{$paths['root']}/{$paths['wp-parent']}/wp-config.php"
        );
    }

    /**
     * @inheritdoc
     */
    public function run(\ArrayAccess $paths)
    {
        $register = $this->config['register-theme-folder'];
        $register === 'ask' and $this->askForRegister();

        $from = $this->filesystemUtil->normalizePath("{$paths['root']}/{$paths['wp-parent']}");

        $relPath = function ($to, $addRoot = true) use ($from, $paths) {
            $addRoot and $to = "{$paths['root']}/$to";

            return $this->filesystemUtil->findShortestPathCode($from, $to, true);
        };

        $relUrl = function ($path) use ($from, $paths) {
            if (!$paths['wp-parent']) {
                return $this->filesystemUtil->normalizePath($path);
            }

            $shortest = $this->filesystemUtil->findShortestPath($from, "{$paths['root']}/{$path}");
            strpos($shortest, './') === 0 and $subdir = substr($shortest, 2);

            return $shortest;
        };

        $vars = [
            'VENDOR_PATH' => $relPath($paths['vendor']),
            'ENV_REL_PATH' => $relPath($paths['root'], false),
            'WP_INSTALL_PATH' => $relPath($paths['wp']),
            'WP_CONTENT_PATH' => $relPath($paths['wp-content']),
            'REGISTER_THEME_DIR' => $register ? 'true' : 'false',
            'ENV_FILE_NAME' => $this->config['env-file'],
            'WP_SITEURL' => $relUrl($paths['wp']),
            'WP_CONTENT_URL' => $relUrl($paths['wp-content']),
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
