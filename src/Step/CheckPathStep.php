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
use WeCodeMore\WpStarter\Utils\Paths;

/**
 * Steps that check that all paths WP Starter needs have been recognized properly ad exist.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class CheckPathStep implements BlockingStepInterface, FileStepInterface, PostProcessStepInterface
{
    const NAME = 'check-paths';

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var \WeCodeMore\WpStarter\Utils\Config
     */
    private $config;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Filesystem
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
     * @param \WeCodeMore\WpStarter\Utils\IO $io
     * @param \WeCodeMore\WpStarter\Utils\Filesystem $filesystem
     */
    public function __construct(IO $io, Filesystem $filesystem)
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
    public function run(Paths $paths, $verbosity)
    {
        $this->paths = $paths;
        $wpContent = $paths->wp_content();

        $toCheck = [
            $paths->wp_starter(),
            realpath($paths->vendor('/autoload.php')),
            realpath($paths->wp('/wp-settings.php')),
            $wpContent
        ];

        if (array_filter($toCheck) !== $toCheck) {
            $this->error = 'WP Starter was not able to find some required folder or files.';

            return self::ERROR;
        }

        if (strpos($wpContent, $paths->wp_parent()) !== 0) {
            $this->error =
                'WP content folder must share parent folder with WP folder, or be contained in it.'
                . ' Use the "wordpress-content-dir" setting to properly set it';

            return self::ERROR;
        }

        // no love for this, but https://core.trac.wordpress.org/ticket/31620 makes it necessary
        if ($this->config[Config::MOVE_CONTENT] !== true && $paths->wp_content()) {
            $this->themeDir = $this->filesystem->createDir("{$wpContent}/themes");
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
        return 'All <comment>paths recognized</comment>.';
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
