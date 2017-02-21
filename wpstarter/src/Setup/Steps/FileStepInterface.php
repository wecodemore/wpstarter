<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup\Steps;

use ArrayAccess;

/**
 * A step that saves a file.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
interface FileStepInterface extends StepInterface
{
    /**
     * Returns the target path of the file the step will create.
     *
     * @param \ArrayAccess $paths
     *
     * @return string
     */
    public function targetPath(ArrayAccess $paths);
}
