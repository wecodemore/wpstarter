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

use ArrayAccess;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\FileBuilder;

/**
 * Steps that generates index.php in root folder.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class IndexStep implements FileStepInterface, BlockingStepInterface
{
    /**
     * @var \WCM\WPStarter\Setup\FileBuilder
     */
    private $builder;

    /**
     * @var array
     */
    private $vars;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @param \WCM\WPStarter\Setup\FileBuilder $builder
     */
    public function __construct(FileBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, ArrayAccess $paths)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function targetPath(ArrayAccess $paths)
    {
        return rtrim($paths['root'].'/'.$paths['site-dir'], '/').'/index.php';
    }

    /**
     * @inheritdoc
     */
    public function run(ArrayAccess $paths)
    {
        $this->vars = array('WP_INSTALL_PATH' => $paths['wp']);
        $build = $this->builder->build($paths, 'index.example', $this->vars);
        if (! $this->builder->save($build, dirname($this->targetPath($paths)), 'index.php')) {
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
