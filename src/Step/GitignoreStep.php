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

use WeCodeMore\WpStarter\Utils\Config;
use WeCodeMore\WpStarter\Utils\Filesystem;
use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\FileBuilder;
use WeCodeMore\WpStarter\Utils\Paths;
use WeCodeMore\WpStarter\Utils\UrlDownloader;

/**
 * Step that save .gitignore in root folder. It is generated based on settings or downloaded.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
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
     * @var Config
     */
    private $config;

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
     * @var \WeCodeMore\WpStarter\Utils\Paths
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
     * @param \WeCodeMore\WpStarter\Utils\IO $io
     * @param \WeCodeMore\WpStarter\Utils\Filesystem $filesystem
     * @param \WeCodeMore\WpStarter\Utils\FileBuilder $builder
     */
    public function __construct(IO $io, Filesystem $filesystem, FileBuilder $builder)
    {
        $this->io = $io;
        $this->filesystem = $filesystem;
        $this->builder = $builder;
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
        if ($this->config[Config::GITIGNORE] !== false) {
            $this->config = $config;
            $this->paths = $paths;

            return true;
        }

        return false;
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
    public function run(Paths $paths, $verbosity)
    {
        $config = $this->config[Config::GITIGNORE];

        if (!is_string($config) || $config === 'ask') {
            return $this->create($paths);
        }

        $realpath = realpath($paths->root($config));

        if ($realpath && is_file($realpath)) {
            $this->action = 'copy';
            $done = $this->filesystem->copyFile($realpath, $this->targetPath($paths));
            $done or $this->error = 'Error on saving .gitignore.';

            return $done ? self::SUCCESS : self::ERROR;
        }

        if (!filter_var($config, FILTER_VALIDATE_URL)) {
            $this->error = "{$config} is not a valid URL.";

            return self::ERROR;
        }

        return $this->download($paths);
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

        $env = $this->paths->relative(Paths::ROOT, $this->config[Config::ENV_FILE], true);
        $env === '/.env' and $env = '.env';
        $config = $this->paths->relative(Paths::WP_PARENT, 'wp-config.php', true);

        while ((!$f['env'] || !$f['config']) && $content) {
            $line = trim(array_shift($content));
            $f['env'] = $f['env'] || in_array($line, [$env, ltrim($env, './')], true);
            $f['config'] = $f['config'] || in_array($line, [$config, ltrim($config, './')], true);
        }

        if ($f['env'] && $f['config']) {
            return;
        }

        $to_ignore = [];
        $f['env'] or $to_ignore[] = $this->config[Config::ENV_FILE];
        $f['config'] or $to_ignore[] = 'wp-config.php';

        $lines = [
            '.gitignore was found in your project folder.',
            'But be sure to ignore ' . implode(' and ', $to_ignore) . '.',
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

        $remote = new UrlDownloader($this->config[Config::GITIGNORE], $this->io);
        if ($remote->save($this->targetPath($paths))) {
            return self::SUCCESS;
        }

        $this->error = 'Error on downloading and saving .gitignore: ' . $remote->error();

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
        $gitIgnore = $this->config[Config::GITIGNORE];

        $this->action = 'create';
        $toDo = is_array($gitIgnore) ? $gitIgnore : self::DEFAULTS;
        $custom = isset($toDo[self::CUSTOM]) ? (array)$toDo[self::CUSTOM] : [];
        unset($toDo[self::CUSTOM]);

        $allPaths = [
            $paths->relative(Paths::ROOT, $this->config[Config::ENV_FILE]),
            $paths->relative(Paths::WP_PARENT, 'wp-config.php'),
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
