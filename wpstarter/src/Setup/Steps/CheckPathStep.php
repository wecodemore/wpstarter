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
use WCM\WPStarter\Setup\FileBuilder;
use WCM\WPStarter\Setup\Filesystem;
use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\Paths;

/**
 * Steps that check that all paths WP Starter needs have been recognized properly ad exist.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class CheckPathStep implements BlockingStepInterface, FileStepInterface, PostProcessStepInterface
{
    const NAME = 'check-paths';

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var \WCM\WPStarter\Setup\Config
     */
    private $config;

    /**
     * @var \WCM\WPStarter\Setup\Filesystem
     */
    private $filesystem;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var bool
     */
    private $themeDir = true;

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
        return new static($filesystem);
    }

    /**
     * @param \WCM\WPStarter\Setup\Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
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
        $this->config = $config;

        return true;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function run(Paths $paths)
    {
        $this->paths = $paths;
        $toCheck = [
            realpath($paths->absolute(Paths::WP_STARTER)),
            realpath($paths->absolute(Paths::VENDOR, '/autoload.php')),
            realpath($paths->absolute(Paths::WP, '/wp-settings.php')),
        ];

        if (array_filter($toCheck) !== $toCheck) {
            $this->error = 'WP Starter was not able to find valid folder settings.';

            return self::ERROR;
        }

        if (
            $paths->wp_content()
            && $paths->wp_parent()
            && strpos(trim($paths->wp_content(), '\\/'), trim($paths->wp_parent(), '\\/')) !== 0
        ) {
            $this->error =
                'Content folder must share parent folder with WP folder, or be contained in it.'
                . ' Use the "wordpress-content-dir" setting to properly set it';

            return self::ERROR;
        }
        // no love for this, but https://core.trac.wordpress.org/ticket/31620 makes it necessary
        if ($this->config[Config::MOVE_CONTENT] !== true && $paths->wp_content()) {
            $themeDir = $paths->absolute(Paths::WP_CONTENT, '/themes');
            $this->themeDir = $this->filesystem->createDir($themeDir);
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
        return 'All paths recognized.';
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function postProcess(IO $io)
    {
        if (!$this->themeDir) {
            $lines = [
                'Default theme folder:',
                '"' . $this->paths->wp_content('/themes') . '" does not exist.',
                'The site may be unusable until you create it (even empty).',
            ];
            $io->block($lines, 'red', true);
        }
    }
}
