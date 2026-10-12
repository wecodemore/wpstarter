<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Step\Step;
use WeCodeMore\WpStarter\Util\Paths;

class DummyStep implements Step
{
    public string $name = 'dummy';
    public bool $allowed = true;
    public bool $success = true;

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        return $this->allowed;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        return $this->success ? Step::SUCCESS : Step::ERROR;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return 'Error!';
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return 'Success!';
    }
}
