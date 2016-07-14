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

/**
 * Steps that generates index.php in root folder.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class IndexStep implements FileCreationStepInterface, BlockingStepInterface
{
    /**
     * @var \WCM\WPStarter\Setup\FileBuilder
     */
    private $builder;

    /**
     * @var \WCM\WPStarter\Setup\Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $vars;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @param \WCM\WPStarter\Setup\IO          $io
     * @param \WCM\WPStarter\Setup\Filesystem  $filesystem
     * @param \WCM\WPStarter\Setup\FileBuilder $filebuilder
     * @return static
     */
    public static function instance(
        IO $io,
        Filesystem $filesystem,
        FileBuilder $filebuilder
    ) {
        return new static($filebuilder, $filesystem);
    }

    /**
     * @param \WCM\WPStarter\Setup\FileBuilder $builder
     * @param \WCM\WPStarter\Setup\Filesystem  $filesystem
     */
    public function __construct(FileBuilder $builder, Filesystem $filesystem)
    {
        $this->builder = $builder;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return 'build-index';
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, \ArrayAccess $paths)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function targetPath(\ArrayAccess $paths)
    {
        return rtrim($paths['root'].'/'.$paths['wp-parent'], '/').'/index.php';
    }

    /**
     * @inheritdoc
     */
    public function run(\ArrayAccess $paths)
    {
        $n = count(explode('/', str_replace('\\', '/', $paths['wp']))) - 1;
        $rootPathRel = str_repeat('dirname(', $n).'__DIR__'.str_repeat(')', $n);
        $this->vars = ['BOOTSTRAP_PATH' => $rootPathRel.".'/{$paths['wp']}/wp-blog-header.php'"];
        $build = $this->builder->build($paths, 'index.example', $this->vars);
        if (! $this->filesystem->save($build, dirname($this->targetPath($paths)).'/index.php')) {
            $this->error = 'Error on create index.php.';

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
