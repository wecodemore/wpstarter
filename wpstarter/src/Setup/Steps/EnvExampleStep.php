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

use WCM\WPStarter\Setup\Filesystem;
use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\FileBuilder;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\Paths;
use WCM\WPStarter\Setup\UrlDownloader;

/**
 * Steps that stores .env.example in root folder. It is copied from a default file or downloaded.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class EnvExampleStep implements FileCreationStepInterface, OptionalStepInterface, PostProcessStepInterface
{
    const NAME = 'build-env-example';

    /**
     * @var \WCM\WPStarter\Setup\IO
     */
    private $io;

    /**
     * @var \WCM\WPStarter\Setup\Config
     */
    private $config;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var \WCM\WPStarter\Setup\Filesystem
     */
    private $filesystem;

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
        return new static($io, $filebuilder, $filesystem);
    }

    /**
     * @param \WCM\WPStarter\Setup\IO $io
     * @param \WCM\WPStarter\Setup\FileBuilder $builder
     * @param \WCM\WPStarter\Setup\Filesystem $filesystem
     */
    public function __construct(IO $io, FileBuilder $builder, Filesystem $filesystem)
    {
        $this->io = $io;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return 'build-env-example';
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, Paths $paths)
    {
        $this->config = $config;
        $this->paths = $paths;
        $env = $paths['root'] . '/' . ltrim($config['env-file'], '\\/');

        return $config['env-example'] !== false && !is_file($env);
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function targetPath(Paths $paths)
    {
        return $paths->root('.env.example');
    }

    /**
     * @inheritdoc
     */
    public function askConfirm(Config $config, IO $io)
    {
        if ($config['env-example'] === 'ask') {
            $lines = [
                'Do you want to save .env.example file to',
                'your project folder?',
            ];

            return $io->confirm($lines, true);
        }

        return true;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function run(Paths $paths)
    {
        $dest = $this->targetPath($paths);

        $config = $this->config[Config::ENV_EXAMPLE];

        if (is_string($config) && $config !== 'ask') {
            $realpath = realpath($paths->root($config));
            if ($realpath && is_file($realpath)) {
                return $this->copy($paths, $dest, $realpath);
            } elseif (filter_var($config, FILTER_VALIDATE_URL)) {
                return $this->download($config, $dest);
            }
            $this->error = "{$config} is not a valid url not a valid relative path.";

            return self::ERROR;
        }

        return $this->copy($paths, $dest);
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function postProcess(IO $io)
    {
        if (!is_file($this->paths->root($this->config[Config::ENV_FILE]))) {
            $lines = [
                'Remember that to make your site fully functional',
                'you either need to have an .env file with at least DB settings',
                'or set them in environment variables in some other way (e.g. via webserver).',
            ];

            $io->block($lines, 'yellow', false);
        }
    }

    /**
     * Download a remote .env.example in root folder.
     *
     * @param  string $url
     * @param  string $dest
     * @return int
     * @throws \RuntimeException
     */
    private function download($url, $dest)
    {
        $remote = new UrlDownloader($url, $this->io);
        if (!$remote->save($dest)) {
            $this->error = "Error downloading and saving {$url}: " . $remote->error() . '.';

            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * Copy a .env.example in root folder.
     *
     * @param  Paths $paths
     * @param  string $dest
     * @param  string|null $source
     * @return int
     * @throws \InvalidArgumentException
     */
    private function copy(Paths $paths, $dest, $source = null)
    {
        if ($source === null) {
            $source = $paths->absolute(Paths::WP_STARTER, 'templates/.env.example');
        }

        if ($this->filesystem->copyFile($source, $dest)) {
            return self::SUCCESS;
        }

        $this->error = 'Error on copy default .env.example in root folder.';

        return self::ERROR;
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
    public function skipped()
    {
        return '  - env.example copy skipped.';
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        return '<comment>env.example</comment> saved successfully.';
    }
}
