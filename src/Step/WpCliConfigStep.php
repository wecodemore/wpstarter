<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util;

final class WpCliConfigStep implements FileCreationStepInterface
{
    const NAME = 'wp-cli-yml';

    /**
     * @var Util\FileBuilder
     */
    private $builder;

    /**
     * @var Util\Filesystem
     */
    private $filesystem;

    /**
     * @param Util\Locator $locator
     */
    public function __construct(Util\Locator $locator)
    {
        $this->builder = $locator->fileBuilder();
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
     * @param Util\Paths $paths
     * @return string
     */
    public function targetPath(Util\Paths $paths): string
    {
        return $paths->root('/wp-cli.yml');
    }

    /**
     * @param Config $config
     * @param Util\Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Util\Paths $paths): bool
    {
        return true;
    }

    /**
     * @param Config $config
     * @param Util\Paths $paths
     * @return int
     */
    public function run(Config $config, Util\Paths $paths): int
    {
        $built = $this->builder->build(
            $paths,
            'wp-cli.yml',
            ['WP_INSTALL_PATH' => $paths->relativeToRoot(Util\Paths::WP)]
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
        return 'Error while creating wp-cli.yml';
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return '<comment>wp-cli.yml</comment> saved successfully.';
    }
}