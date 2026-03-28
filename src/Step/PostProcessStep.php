<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Io\Io;

/**
 * Steps that run a routine after all steps have been processed.
 */
interface PostProcessStep extends Step
{
    /**
     * Runs after all steps have been processed. Useful to print some messages or do some cleanup.
     *
     * @param Io $io
     * @return void
     */
    public function postProcess(Io $io): void;
}
