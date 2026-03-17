<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Util\Paths;

/**
 * A step that creates and saves a file.
 */
interface FileCreationStep extends Step
{
    /**
     * Returns the target path of the file the step will create.
     *
     * @param  Paths $paths
     * @return string
     */
    public function targetPath(Paths $paths): string;
}
