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
 */
final class MuLoaderStep implements FileCreationStepInterface, BlockingStep
{
    const NAME = 'build-mu-loader';
    const TARGET_FILE_NAME = 'wpstarter-mu-loader.php';

    /**
     * @var \WeCodeMore\WpStarter\Util\FileBuilder
     */
    private $builder;

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->filesystem = $locator->filesystem();
        $this->builder = $locator->fileBuilder();
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
        return $config[Config::MU_PLUGIN_LIST]->notEmpty();
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
        $pluginsConfig = $config[Config::MU_PLUGIN_LIST]->unwrap();
        $muPluginsPathList = is_array($pluginsConfig)
            ? array_filter($pluginsConfig, 'is_string')
            : [];

        $built = $this->builder->build(
            $paths,
            'wpstarter-mu-loader.php',
            ['MU_PLUGINS_LIST' => implode(', ', $muPluginsPathList)]
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
