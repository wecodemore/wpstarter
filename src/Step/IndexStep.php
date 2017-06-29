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
 * Steps that generates index.php in root folder.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class IndexStep implements FileCreationStepInterface, BlockingStepInterface
{
    const NAME = 'build-index';

    /**
     * @var \WeCodeMore\WpStarter\Utils\FileBuilder
     */
    private $builder;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Filesystem
     */
    private $filesystem;
    /**
     * @var string
     */
    private $error = '';

    /**
     * @param IO $io
     * @param \WeCodeMore\WpStarter\Utils\Filesystem $filesystem
     * @param \WeCodeMore\WpStarter\Utils\FileBuilder $builder
     */
    public function __construct(IO $io, Filesystem $filesystem, FileBuilder $builder)
    {
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
     */
    public function allowed(Config $config, Paths $paths)
    {
        return true;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function targetPath(Paths $paths)
    {
        return $paths->wp_parent('index.php');
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function run(Paths $paths, $verbosity)
    {
        $from = $paths->wp_parent();
        $to = $paths->wp('index.php');

        $indexPath = $this->filesystem->composerFilesystem()->findShortestPath($from, $to);

        $build = $this->builder->build($paths, 'index.example', ['BOOTSTRAP_PATH' => $indexPath]);

        if (!$this->filesystem->save($build, $this->targetPath($paths))) {
            $this->error = 'Error creating index.php.';

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
        return '<comment>index.php</comment> saved successfully.';
    }
}
