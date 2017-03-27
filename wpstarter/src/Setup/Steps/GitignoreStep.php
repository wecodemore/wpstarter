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

use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\Filesystem;
use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\FileBuilder;
use WCM\WPStarter\Setup\Paths;
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
    const NAME = 'build-gitignore';

    const WP = 'wp';
    const WP_CONTENT = 'wp-content';
    const VENDOR = 'vendor';
    const COMMON = 'common';
    const CUSTOM = 'custom';

    const DEFAULTS = [
        self::WP_CONTENT => true,
        self::VENDOR     => true,
        self::COMMON     => false,
        self::CUSTOM     => [],
    ];

    /**
     * @var mixed
     */
    private $config;

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
     * @var \WCM\WPStarter\Setup\Paths
     */
    private $paths;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var string
     */
    private $action = '';

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
        return self::NAME;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function allowed(Config $config, Paths $paths)
    {
        $this->config = $config[Config::GITIGNORE];
        $this->paths = $paths;

        return $this->config !== false;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function targetPath(Paths $paths)
    {
        return $paths->root('.gitignore');
    }

    /**
     * @inheritdoc
     */
    public function askConfirm(Config $config, IO $io)
    {
        if ($config[Config::GITIGNORE] === 'ask') {
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
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function run(Paths $paths)
    {
        if (is_string($this->config) && $this->config !== 'ask') {
            $realpath = realpath($paths->root($this->config));
            if ($realpath && is_file($realpath)) {
                $this->action = 'copy';
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
     * @throws \InvalidArgumentException
     */
    public function postProcess(IO $io)
    {
        if ($this->error) {
            return;
        }

        $target = $this->targetPath($this->paths);
        $isThere = is_file($target);

        if (!$isThere) {
            $lines = [
                '.gitignore was not found in your project folder,',
                '.env and wp-config.php files should be ignored, at least.',
            ];

            $io->block($lines, 'yellow', false);

            return;
        }

        if ($this->action === 'create') {
            $lines = [
                '.gitignore was saved in your project folder,',
                'feel free to edit it, but be sure to ignore',
                '.env and wp-config.php files.',
            ];

            $io->block($lines, 'yellow', false);

            return;
        }

        /*
         * A .gitignore is there, but WP Starter did not created it.
         * Let's ensure it ignores both wp-config.php and .env file.
         */
        $f = ['env' => false, 'config' => false];
        $content = file($target);
        $config = $this->paths->wp_parent('wp-config.php');
        $env = $this->config[Config::ENV_FILE];
        while ((!$f['env'] || !$f['config']) && $content) {
            $line = array_shift($content);
            $f['env'] = $f['env'] || in_array(trim($line), [$env, "/$env"], true);
            $f['config'] = $f['config'] || in_array(trim($line), [$config, "/$config"], true);
        }

        if ($f['env'] && $f['config']) {
            return;
        }

        $lines = [
            '.gitignore was found in your project folder.',
            'But be sure to ignore .env and wp-config.php files.',
        ];

        $io->block($lines, 'yellow', false);
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
     * @param  Paths $paths
     * @return int
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    private function download(Paths $paths)
    {
        $this->action = 'download';
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
     * @param  Paths $paths
     * @return int
     * @throws \InvalidArgumentException
     */
    private function create(Paths $paths)
    {
        $this->action = 'create';
        $toDo = is_array($this->config) ? $this->config : self::DEFAULTS;
        $custom = isset($toDo[self::CUSTOM]) ? (array)$toDo[self::CUSTOM] : [];
        unset($toDo[self::CUSTOM]);

        $allPaths = [
            $this->config[Config::ENV_FILE],
            $paths->wp_parent('wp-config.php'),
        ];

        empty($toDo[self::WP]) and $allPaths[] = $paths->wp();
        empty($toDo[self::WP_CONTENT]) and $allPaths[] = $paths->wp_content();
        empty($toDo[self::VENDOR]) and $allPaths[] = $paths->vendor();

        $allPaths = array_map(function ($path) {
            return str_replace('\\', '/', $path);
        }, array_merge($allPaths, $custom));

        $allPaths = array_unique(array_filter($allPaths));

        $content = '### WP Starter' . PHP_EOL . implode(PHP_EOL, $allPaths) . PHP_EOL;

        if (!empty($toDo[self::COMMON])) {
            $common = trim($this->builder->build($paths, '.gitignore.example'));
            $content = $common ? $content . PHP_EOL . PHP_EOL . $common : $content;
        }

        if (!$this->filesystem->save($content, $paths->root('.gitignore'))) {
            $this->error = 'WP Starter was not able to create .gitignore file.';

            return self::ERROR;
        }

        return self::SUCCESS;
    }

}
