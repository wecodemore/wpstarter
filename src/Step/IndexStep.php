<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Steps that generates index.php in webroot folder.
 *
 * Additional index.php is necessary to have WordPress in its own folder.
 * @see https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory
 */
final class IndexStep implements FileCreationStep, BlockingStep
{
    public const NAME = 'index';

    /**
     * @var \WeCodeMore\WpStarter\Util\FileContentBuilder
     */
    private $builder;

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var \Composer\Util\Filesystem
     */
    private $composerFilesystem;

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->builder = $locator->fileContentBuilder();
        $this->filesystem = $locator->filesystem();
        $this->composerFilesystem = $locator->composerFilesystem();
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
     * @param Paths $paths
     * @return string
     */
    public function targetPath(Paths $paths): string
    {
        return $paths->wpParent('index.php');
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $from = $paths->wpParent('index.php');
        $to = $paths->wp('index.php');

        $indexPath = $this->composerFilesystem->findShortestPath($from, $to);

        $built = $this->builder->build($paths, 'index.php', ['BOOTSTRAP_PATH' => "/{$indexPath}"]);

        if (!$this->filesystem->writeContent($built, $this->targetPath($paths))) {
            return Step::ERROR;
        }

        return Step::SUCCESS;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return 'Creation of index.php failed.';
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return '<comment>index.php</comment> saved successfully.';
    }
}
