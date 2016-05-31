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
use WCM\WPStarter\Setup\OverwriteHelper;
use WCM\WPStarter\Setup\UrlDownloader;
use ArrayAccess;
use Exception;

/**
 * Steps that stores .env.example in root folder. It is copied from a default file or downloaded.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class EnvExampleStep implements FileCreationStepInterface, OptionalStepInterface, PostProcessStepInterface
{
    /**
     * @var \WCM\WPStarter\Setup\IO
     */
    private $io;

    /**
     * @var \WCM\WPStarter\Setup\Config
     */
    private $config;

    /**
     * @var \ArrayAccess
     */
    private $paths;

    /**
     * @var \WCM\WPStarter\Setup\FileBuilder
     */
    private $builder;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @param \WCM\WPStarter\Setup\IO              $io
     * @param \WCM\WPStarter\Setup\Filesystem      $filesystem
     * @param \WCM\WPStarter\Setup\FileBuilder     $filebuilder
     * @param \WCM\WPStarter\Setup\OverwriteHelper $overwriteHelper
     * @return static
     */
    public static function instance(
        IO $io,
        Filesystem $filesystem,
        FileBuilder $filebuilder,
        OverwriteHelper $overwriteHelper
    ) {
        return new static($io, $filebuilder);
    }

    /**
     * @param \WCM\WPStarter\Setup\IO          $io
     * @param \WCM\WPStarter\Setup\FileBuilder $builder
     */
    public function __construct(IO $io, FileBuilder $builder)
    {
        $this->io = $io;
        $this->builder = $builder;
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, ArrayAccess $paths)
    {
        $this->config = $config;
        $this->paths = $paths;
        $env = $paths['root'].'/'.ltrim($config['env-file'], '\\/');

        return $config['env-example'] !== false && ! is_file($env);
    }

    /**
     * @inheritdoc
     */
    public function targetPath(ArrayAccess $paths)
    {
        return $paths['root'].'/.env.example';
    }

    /**
     * @inheritdoc
     */
    public function question(Config $config, IO $io)
    {
        if ($config['env-example'] === 'ask') {
            $lines = [
                'Do you want to save .env.example file to',
                'your project folder?',
            ];

            return $io->ask($lines, true);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function run(ArrayAccess $paths)
    {
        $dest = $this->targetPath($paths);
        $config = $this->config['env-example'];
        if (is_string($config) && $config !== 'ask') {
            $realpath = realpath($paths['root'].'/'.$config);
            if ($realpath && is_file($realpath)) {
                return $this->copy($paths, $dest, $realpath);
            } elseif (filter_var($config, FILTER_VALIDATE_URL)) {
                return $this->download($config, $dest, $paths);
            }
            $this->error = "{$config} is not a valid url not a valid relative path.";

            return self::ERROR;
        }

        return $this->copy($paths, $dest);
    }

    /**
     * @inheritdoc
     */
    public function postProcess(IO $io)
    {
        if (! is_file($this->paths['root'].'/'.ltrim($this->config['env-file'], '/\\'))) {
            $lines = [
                'Remember you need a .env file with DB settings',
                'to make your site fully functional.',
            ];

            $io->block($lines, 'yellow', false);
        }
    }

    /**
     * Download a remote .env.example in root folder.
     *
     * @param  string       $url
     * @param  string       $dest
     * @param  \ArrayAccess $paths
     * @return int
     */
    private function download($url, $dest, ArrayAccess $paths)
    {
        if (! UrlDownloader::checkSoftware()) {
            $this->io->comment('WP Starter needs cUrl installed to download files from url.');

            return $this->copy($paths, $dest);
        }
        $remote = new UrlDownloader($url);
        if (! $remote->save($dest)) {
            $this->error = 'Error on downloading and save .env.example: '.$remote->error().'.';

            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * Copy a .env.example in root folder.
     *
     * @param  \ArrayAccess $paths
     * @param               $dest
     * @param  null         $source
     * @return int
     */
    private function copy(ArrayAccess $paths, $dest, $source = null)
    {
        if (is_null($source)) {
            $pieces = [$paths['starter'], 'templates'];
            if (! $this->config['is-root']) {
                array_unshift($pieces, $paths['root']);
            }
            $source = implode('/', array_merge($pieces, ['.env.example']));
        }
        try {
            if (copy($source, $dest)) {
                return self::SUCCESS;
            }
            $this->error = 'Error on copy default .env.example in root folder.';
        } catch (Exception $e) {
            $this->error = 'Error on copy default .env.example in root folder.';
        }

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
