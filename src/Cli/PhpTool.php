<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Cli;

use WeCodeMore\WpStarter\Util\Io;
use WeCodeMore\WpStarter\Util\Paths;

interface PhpTool
{

    /**
     * @return string
     */
    public function niceName(): string;

    /**
     * @return string
     */
    public function packageName(): string;

    /**
     * @return string
     */
    public function pharUrl(): string;

    /**
     * @param Paths $paths
     * @return string
     */
    public function pharTarget(Paths $paths): string;

    /**
     * @param string $packageVendorPath
     * @return string
     */
    public function filesystemBootstrap(string $packageVendorPath): string;

    /**
     * @return string
     */
    public function minVersion(): string;

    /**
     * @param string $pharPath
     * @param Io $io
     * @return bool
     */
    public function checkPhar(string $pharPath, Io $io): bool;

    /**
     * @param Paths $paths
     * @param \ArrayAccess $env
     * @return array
     */
    public function processEnvVars(Paths $paths, \ArrayAccess $env): array;
}
