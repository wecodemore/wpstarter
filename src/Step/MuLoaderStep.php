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

/**
 * Steps that generates wpstarter-mu-loader.php in mu-plugins folder.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class MuLoaderStep implements FileCreationStepInterface, BlockingStepInterface
{
    const NAME = 'build-mu-loader';

    /**
     * @var \WeCodeMore\WpStarter\Utils\FileBuilder
     */
    private $builder;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Filesystem
     */
    private $filesystem;

    /**
     * @var string[]
     */
    private $plugins_list;

    /**
     * @param \WeCodeMore\WpStarter\Utils\IO $io
     * @param \WeCodeMore\WpStarter\Utils\Filesystem $filesystem
     * @param \WeCodeMore\WpStarter\Utils\FileBuilder $filebuilder
     */
    public function __construct(IO $io, Filesystem $filesystem, FileBuilder $filebuilder)
    {
        $this->filesystem = $filesystem;
        $this->builder = $filebuilder;
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
        if ($config[Config::MU_PLUGIN_LIST]) {

            $this->plugins_list = $config[Config::MU_PLUGIN_LIST];

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
        return $paths->wp_content('mu-plugins/wpstarter-mu-loader.php');
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function run(Paths $paths, $verbosity)
    {
        $list = array_reduce($this->plugins_list, function ($list, $plugin) use ($paths) {

            $to = $paths->root($plugin);
            $from = $paths->wp_content('mu-plugins');

            $list[] = $this->filesystem->composerFilesystem()->findShortestPath($from, $to);

            return $list;
        });

        $build = $this->builder->build(
            $paths,
            'wpstarter-mu-loader.example',
            ['MU_PLUGINS_LIST' => implode(",\n", $list)]
        );

        if (!$this->filesystem->save($build, $this->targetPath($paths))) {
            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * @inheritdoc
     */
    public function error()
    {
        return 'Error creating MU plugin loader.';
    }

    /**
     * @inheritdoc
     */
    public function success()
    {

        return '<comment>MU plugin loader</comment> saved successfully.';
    }
}
