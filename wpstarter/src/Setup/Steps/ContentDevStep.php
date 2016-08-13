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

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class ContentDevStep implements OptionalStepInterface
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
     * @var string
     */
    private $operation = 'symlink';

    /**
     * @param \WCM\WPStarter\Setup\IO $io
     */
    public function __construct(IO $io)
    {
        $this->io = $io;
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return 'publish-content-dev';
    }

    /**
     * Return true if the step is allowed, i.e. the run method have to be called or not
     *
     * @param  \WCM\WPStarter\Setup\Config $config
     * @param  \ArrayAccess $paths
     * @return bool
     */
    public function allowed(Config $config, \ArrayAccess $paths)
    {
        $this->config = $config;

        return $config['content-dev-op'] && $config['content-dev-dir'];
    }

    /**
     * @inheritdoc
     */
    public function askConfirm(Config $config, IO $io)
    {
        $dir = $this->config['content-dev-dir'];
        if ($this->config['content-dev-op'] !== 'ask' || !$dir) {
            return true;
        }

        $answers = ['s' => '[S]imlink', 'c' => '[C]opy', 'n' => '[N]othing'];
        $operation = $this->io->ask([
            'Which operation do you want to perform',
            "for content-dev folders in /{$dir}",
            'to make them available in WP content dir?'
        ], $answers, 's');

        is_string($operation) and $operation = strtolower($operation);

        if ($operation === 'n') {
            return false;
        }

        $operation === 'c' and $this->operation = 'copy';
        $operation === 's' and $this->operation = 'symlink';

        return true;
    }

    /**
     * @inheritdoc
     */
    public function run(\ArrayAccess $paths)
    {
        $dir = $this->config['content-dev-dir'];
        if (!$dir) {
            return self::NONE;
        }

        $source = $paths['root'] . '/' . $dir;
        if (!is_dir($source)) {
            $this->operation = '';

            return self::ERROR;
        }

        if (!in_array($this->operation, ['copy', 'symlink'])) {
            return self::ERROR;
        }

        $target = $paths['root'] . '/' . $paths['wp-content'];
        $filesystem = new Filesystem();

        $sourceDirs = glob($source . '/*', GLOB_NOSORT | GLOB_ONLYDIR);
        if (!$sourceDirs) {
            return self::NONE;
        }

        if ($this->operation === 'copy') {
            return $filesystem->copyDir($source, $target) ? self::SUCCESS : self::ERROR;
        }

        $done = 0;
        $all = count($sourceDirs);

        foreach ($sourceDirs as $sourceDir) {
            $filesystem->symlink($sourceDir, $target . '/' . basename($sourceDir)) and $done++;
        }

        return $done === $all ? self::SUCCESS : self::ERROR;
    }

    /**
     * @inheritdoc
     */
    public function error()
    {
        $dir = $this->config['content-dev-dir'];
        if (in_array($this->operation, ['copy', 'symlink'])) {
            return "Some errors occurred while {$this->operation}ing content-dev dirs from /{$dir}.";
        }

        return "Some errors occurred while publishing content-dev dirs from /{$dir}.";
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        $dir = $this->config['content-dev-dir'];
        if (in_array($this->operation, ['copy', 'symlink'])) {
            $operation = $this->operation === 'copy' ? 'copied' : 'symlinked';

            return "Content-dev dirs {$operation} successfully from /{$dir}.";
        }

        return "Content-dev dirs publishing done successfully from /{$dir}.";
    }

    /**
     * @inheritdoc
     */
    public function skipped()
    {
        return 'Content-dev publishing skipped.';
    }
}
