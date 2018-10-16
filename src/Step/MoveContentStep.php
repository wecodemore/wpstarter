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
 * Step that moves wp-content contents from WP package folder to project wp-content folder.
 *
 * WP Starter assumes that WordPress is installed via Composer, and that very likely includes
 * default themes and plugins that are shipped with WordPress.
 * Because WP Starter also use a different wp-content folder, placed outside WordPress folder,
 * the default themes and plugins are not recognized by WordPress.
 * This step that can be enabled via configuration copy the default plugins and themes from the
 * WP wp-content folder to the project wp-content folder, so that WordPress can recognize them.
 */
final class MoveContentStep implements OptionalStep
{
    const NAME = 'move-content';

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var \WeCodeMore\WpStarter\Util\Paths
     */
    private $paths;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->filesystem = $locator->filesystem();
        $this->paths = $locator->paths();
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
        return $config[Config::MOVE_CONTENT]->not(false) && $paths->wpContent();
    }

    /**
     * @param Config $config
     * @param Io $io
     * @return bool
     */
    public function askConfirm(Config $config, Io $io): bool
    {
        if (!$config[Config::MOVE_CONTENT]->is(OptionalStep::ASK)) {
            return true;
        }

        $lines = [
            'Do you want to move default plugins and themes from',
            'WordPress package wp-content dir to content folder:',
            '"' . $this->paths->wpContent() . '"',
        ];

        return $io->askConfirm($lines, true);
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $from = $paths->wp('wp-content');
        $to = $paths->wpContent();

        if ($from === $to) {
            return self::NONE;
        }

        if (!$this->filesystem->createDir($to)) {
            $this->error = "The folder {$to} does not exist and was not possible to create it.";
        }

        return $this->filesystem->moveDir($from, $to) ? self::SUCCESS : self::ERROR;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return trim($this->error);
    }

    /**
     * @return string
     */
    public function skipped(): string
    {
        return '  - wp-content folder contents moving skipped.';
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return '<comment>wp-content</comment> folder contents moved successfully.';
    }
}
