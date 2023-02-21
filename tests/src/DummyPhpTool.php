<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests;

use PHPUnit\Framework\Assert;
use WeCodeMore\WpStarter\Cli\PhpTool;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\Paths;

class DummyPhpTool implements PhpTool
{
    // phpcs:disable
    public $niceName = 'dummy';
    public $packageName = '';
    public $pharUrl = '';
    public $pharTarget = '';
    public $filesystemBootstrap = null;
    public $minVersion = '0';
    public $pharIsValid = false;
    // phpcs:enable

    /**
     * @return string
     */
    public function niceName(): string
    {
        return $this->niceName;
    }

    /**
     * @return string
     */
    public function packageName(): string
    {
        return $this->packageName;
    }

    /**
     * @return string
     */
    public function pharUrl(): string
    {
        return $this->pharUrl;
    }

    /**
     * @param Paths $paths
     * @return string
     */
    public function pharTarget(Paths $paths): string
    {
        return $this->pharTarget;
    }

    /**
     * @param string $packageVendorPath
     * @return string
     */
    public function filesystemBootstrap(string $packageVendorPath): string
    {
        if ($packageVendorPath && $this->filesystemBootstrap === null) {
            $this->filesystemBootstrap = $packageVendorPath;
        }

        return $this->filesystemBootstrap ?? '';
    }

    /**
     * @return string
     */
    public function minVersion(): string
    {
        return $this->minVersion;
    }

    /**
     * @param string $pharPath
     * @param Io $io
     * @return bool
     */
    public function checkPhar(string $pharPath, Io $io): bool
    {
        return $this->pharIsValid;
    }

    /**
     * @param string $command
     * @param Paths $paths
     * @param Io $io
     * @return string
     */
    public function prepareCommand(string $command, Paths $paths, Io $io): string
    {
        $io->write("Dummy!");

        if ($this->filesystemBootstrap) {
            Assert::stringStartsWith("{$this->filesystemBootstrap} ")->evaluate($command);
            $command = substr($command, strlen($this->filesystemBootstrap) + 1);
        }

        if (!$this->filesystemBootstrap && $this->pharTarget) {
            Assert::stringStartsWith("{$this->pharTarget} ")->evaluate($command);
            $command = substr($command, strlen($this->pharTarget) + 1);
        }

        return $command;
    }
}
