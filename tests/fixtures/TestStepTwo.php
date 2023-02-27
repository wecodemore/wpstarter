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

/**
 * This is a test class with a multi-line doc bloc.
 * With a second line.
 *
 * And a third line after a space.
 */
class TestStepTwo implements Step
{
    public function name(): string
    {
        return 'test-step-two';
    }

    public function allowed(Config $config, Paths $paths): bool
    {
        return true;
    }

    public function run(Config $config, Paths $paths): int
    {
        return Step::NONE;
    }

    public function error(): string
    {
       return '';
    }

    public function success(): string
    {
        return '';
    }
}
