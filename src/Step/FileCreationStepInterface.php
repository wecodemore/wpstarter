<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Utils\Paths;

/**
 * A step that saves a file.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
interface FileCreationStepInterface extends FileStepInterface
{
    /**
     * Returns the target path of the file the step will create.
     *
     * @param  Paths $paths
     * @return string
     */
    public function targetPath(Paths $paths);
}