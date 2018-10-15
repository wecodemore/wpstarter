<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Steps that checks if all the paths WP Starter needs have been recognized properly ad exist.
 */
final class CheckPathStep implements BlockingStep, PostProcessStep
{
    const NAME = 'check-paths';

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var bool
     */
    private $themeDir = true;

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->filesystem = $locator->filesystem();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        return true;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $wpContent = $paths->wpContent();

        if (strpos($wpContent, $paths->wpParent()) !== 0) {
            $this->error =
                'WP content folder must share parent folder with WP folder, or be contained in it.'
                . ' Use the "wordpress-content-dir" setting to properly set it';

            return self::ERROR;
        }

        $this->filesystem->createDir($wpContent);

        $toCheck = [
            realpath($paths->vendor('/autoload.php')),
            realpath($paths->wp('/wp-settings.php')),
            $wpContent,
        ];

        if (array_filter($toCheck) !== $toCheck) {
            $this->error = 'WP Starter was not able to find some required folder or files.';

            return self::ERROR;
        }

        // no love for this, but https://core.trac.wordpress.org/ticket/31620 makes it necessary.
        if ($config[Config::MOVE_CONTENT]->not(true) && $paths->wpContent()) {
            $this->themeDir = $this->filesystem->createDir("{$wpContent}/themes");
            // missing plugins dir isn't as serious as missing themes dir, just cause a PHP warning.
            $this->filesystem->createDir("{$wpContent}/plugins");
        }

        return self::SUCCESS;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return 'All <comment>paths recognized</comment>.';
    }

    /**
     * @param Io $io
     */
    public function postProcess(Io $io)
    {
        if (!$this->themeDir) {
            $lines = [
                'Default theme folder does not exist.',
                'The site may be unusable until you create it (even empty).',
            ];

            $io->writeErrorBlock(...$lines);
        }
    }
}
