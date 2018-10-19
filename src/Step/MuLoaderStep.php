<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Steps that generates wpstarter-mu-loader.php in mu-plugins folder.
 *
 * MU plugins are files are supported by Composer installers and so correctly placed in the
 * wp-content/mu-plugins folder. However, Composer will place them in a subdirectory alongside any
 * other file that makes the package (at very least a `composer.json`), but unfortunately WordPress
 * is not able to load MU plugins from subfolders.
 * This step creates a MU plugin, placed in the proper folder, that loads all the MU plugins that
 * Composer placed in subfolder.
 */
final class MuLoaderStep implements FileCreationStepInterface
{
    const NAME = 'build-mu-loader';
    const TARGET_FILE_NAME = 'wpstarter-mu-loader.php';

    /**
     * @var \WeCodeMore\WpStarter\Util\MuPluginList
     */
    private $list;

    /**
     * @var \WeCodeMore\WpStarter\Util\FileContentBuilder
     */
    private $builder;

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $muPlugins = [];

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->list = $locator->muPluginsList();
        $this->filesystem = $locator->filesystem();
        $this->builder = $locator->fileContentBuilder();
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
        $this->muPlugins = $this->list->pluginsList();

        return (bool)$this->muPlugins;
    }

    /**
     * @param Paths $paths
     * @return string
     */
    public function targetPath(Paths $paths): string
    {
        return $paths->wpContent('mu-plugins/' . self::TARGET_FILE_NAME);
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $built = $this->builder->build(
            $paths,
            'wpstarter-mu-loader.php',
            ['MU_PLUGINS_LIST' => implode(', ', $this->muPlugins)]
        );

        if (!$this->filesystem->save($built, $this->targetPath($paths))) {
            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return 'Error creating MU plugin loader.';
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return '<comment>MU plugin loader</comment> saved successfully.';
    }
}
