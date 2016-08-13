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
use WCM\WPStarter\Setup\UrlDownloader;

/**
 * Step that save .gitignore in root folder. It is generated based on settings or downloaded.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class GitignoreStep implements FileCreationStepInterface, OptionalStepInterface, PostProcessStepInterface
{
    /**
     * @var array
     */
    private static $default = [
        'wp' => true,
        'wp-content' => true,
        'vendor' => true,
        'common' => false,
        'custom' => [],
    ];

    /**
     * @var mixed
     */
    private $config;

    /**
     * @var string
     */
    private $env;

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
     * @var string
     */
    private $error = '';

    /**
     * @var bool
     */
    private $found = false;

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
        $this->builder = $builder;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return 'build-gitignore';
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, \ArrayAccess $paths)
    {
        $this->config = $config['gitignore'];
        $this->env = $config['env-file'];
        $this->found = is_file($this->targetPath($paths));

        return $this->config !== false;
    }

    /**
     * @inheritdoc
     */
    public function targetPath(\ArrayAccess $paths)
    {
        return $paths['root'] . '/.gitignore';
    }

    /**
     * @inheritdoc
     */
    public function askConfirm(Config $config, IO $io)
    {
        $this->found = false;
        if ($config['gitignore'] === 'ask') {
            $lines = [
                'Do you want to create a .gitignore file that makes Git ignore',
                ' - files that contain sensible data (wp-config.php, .env)',
                ' - WordPress package folder',
                ' - WordPress content folder',
            ];

            return $io->confirm($lines, true);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function run(\ArrayAccess $paths)
    {
        $this->found = -1;
        if (is_string($this->config) && $this->config !== 'ask') {
            $realpath = realpath($paths['root'] . '/' . $this->config);
            if ($realpath && is_file($realpath)) {
                $done = $this->filesystem->copyDir($realpath, $this->targetPath($paths));
                $done or $this->error = 'Error on saving .gitignore.';

                return $done ? self::SUCCESS : self::ERROR;
            }
            if (!filter_var($this->config, FILTER_VALIDATE_URL)) {
                $this->error = "{$this->config} is not a valid url not a valid relative path.";

                return self::ERROR;
            }

            return $this->download($paths);
        }

        return $this->create($paths);
    }

    /**
     * @inheritdoc
     */
    public function postProcess(IO $io)
    {
        $lines = false;
        if ($this->found === true) {
            $lines = [
                '.gitignore was found in your project folder.',
                'Be sure to ignore .env and wp-config.php files.',
            ];
        } elseif ($this->found === -1 && empty($this->error)) {
            $lines = [
                '.gitignore was saved in your project folder,',
                'feel free to edit it, but be sure to ignore',
                '.env and wp-config.php files.',
            ];
        }
        $lines and $io->block($lines, 'yellow', false);
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
        return '  - .gitignore creation skipped.';
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        return '<comment>.gitignore</comment> saved successfully.';
    }

    /**
     * Download .gitignore from a given url.
     *
     * @param  \ArrayAccess $paths
     * @return int
     */
    private function download(\ArrayAccess $paths)
    {
        $remote = new UrlDownloader($this->config, $this->io);
        if ($remote->save($this->targetPath($paths))) {
            return self::SUCCESS;
        }
        $this->error = 'Error on downloading and saving .gitignore. ' . $remote->error();

        return self::ERROR;
    }

    /**
     * Build .gitignore content based on settings.
     *
     * @param  \ArrayAccess $paths
     * @return int
     */
    private function create(\ArrayAccess $paths)
    {
        $toDo = is_array($this->config) ? $this->config : self::$default;
        $filePaths = array_unique(
            array_filter(
                array_merge(
                    [$this->env, $paths['wp-parent'] . '/wp-config.php'],
                    $toDo['custom'],
                    $this->paths($toDo, $paths)
                )
            )
        );
        $content = '### WP Starter' . PHP_EOL . implode(PHP_EOL, $filePaths) . PHP_EOL;
        if ($toDo['common']) {
            $common = trim($this->builder->build($paths, '.gitignore.example'));
            $content = $common ? $content . PHP_EOL . PHP_EOL . $common : $content;
        }
        if (!$this->filesystem->save($content, $paths['root'] . '/.gitignore')) {
            $this->error = 'WP Starter was not able to create .gitignore file.';

            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array $toDo
     * @param  \ArrayAccess $paths
     * @return array
     */
    private function paths(array $toDo, \ArrayAccess $paths)
    {
        $parsedPaths = [];
        $toCheck = [
            'wp',
            'wp-content',
            'vendor',
        ];

        $filesystem = new FilesystemUtil();

        foreach ($toCheck as $key) {
            $toDo[$key] and $parsedPaths = $this->maybeAdd($paths[$key], $parsedPaths, $filesystem);
        }

        return $parsedPaths;
    }

    /**
     * Takes a path and compare it to already added path to discover if it should be added or not.
     *
     * @param  string $path
     * @param  array $parsedPaths
     * @param \Composer\Util\Filesystem $filesystem
     * @return array
     */
    private function maybeAdd($path, array $parsedPaths, FilesystemUtil $filesystem)
    {
        $rel = $filesystem->normalizePath("{$path}/");
        $targets = $parsedPaths;
        $add = null;

        foreach ($targets as $key => $target) {
            if ($target === $rel || strpos($rel, $target) !== false) {
                $add = false;
                // given path is equal or contained in one of the already added paths
                continue;
            }
            if (strpos($target, $rel) !== false) {
                is_null($add) and $add = true;
                // given path contains one of the already added paths
                unset($parsedPaths[$key]);
                $parsedPaths[] = $rel;
            }
        }
        ($add !== false) and $parsedPaths[] = $rel;

        return array_values(array_unique($parsedPaths));
    }
}
