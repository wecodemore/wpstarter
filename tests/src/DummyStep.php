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
    // phpcs:disable
    public $name = 'dummy';
    public $allowed = true;
    public $success = true;

    // phpcs:enable

    public function name(): string
    {
        return $this->name;
    }

    public function allowed(Config $config, Paths $paths): bool
    {
        return $this->allowed;
    }

    public function run(Config $config, Paths $paths): int
    {
        return $this->success ? Step::SUCCESS : Step::ERROR;
    }

    public function error(): string
    {
        return 'Error!';
    }

    public function success(): string
    {
        return 'Success!';
    }
}
