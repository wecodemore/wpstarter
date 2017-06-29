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

use Composer\Util\Filesystem as FilesystemUtil;
use WeCodeMore\WpStarter\Utils\Filesystem;
use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\Config;
use WeCodeMore\WpStarter\Utils\Paths;

/**
 * Step that moves wp-content contents from WP package folder to project wp-content folder.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class MoveContentStep implements OptionalStepInterface, FileStepInterface
{
    const NAME = 'move-content';

    /**
     * @var \Composer\Util\Filesystem
     */
    private $filesystemUtil;

    /**
     * @var \WeCodeMore\WpStarter\Utils\IO
     */
    private $io;

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
     * @param \WeCodeMore\WpStarter\Utils\IO $io
     * @param \WeCodeMore\WpStarter\Utils\Filesystem $filesystem
     */
    public function __construct(IO $io, Filesystem $filesystem)
    {
        $this->io = $io;
        $this->filesystem = $filesystem;
        $this->filesystemUtil = new FilesystemUtil();
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
        $this->paths = $paths;

        return $config[Config::MOVE_CONTENT] !== false && $paths->wp_content();
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function askConfirm(Config $config, IO $io)
    {
        if ($config[Config::MOVE_CONTENT] !== 'ask') {
            return true;
        }

        $lines = [
            'Do you want to move default plugins and themes from',
            'WordPress package wp-content dir to content folder:',
            '"' . $this->paths->wp_content() . '"',
        ];

        return $io->confirm($lines, true);
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function run(Paths $paths, $verbosity)
    {
        $from = $paths->wp('wp-content');
        $to = $paths->wp_content();

        if ($from === $to) {
            return self::NONE;
        }

        if (!$this->filesystem->createDir($to)) {
            $this->error = "The folder {$to} does not exist and was not possible to create it.";
        }

        return $this->filesystem->moveDir($from, $to) ? self::SUCCESS : self::ERROR;
    }

    /**
     * @inheritdoc
     */
    public function error()
    {
        return trim($this->error);
    }

    /**
     * @inheritdoc
     */
    public function skipped()
    {
        return '  - wp-content folder contents moving skipped.';
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        return '<comment>wp-content</comment> folder contents moved successfully.';
    }
}
