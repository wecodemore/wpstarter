<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\PhpCliTool;

use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\Paths;


/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WeCodeMore\WpStarter
 * @license http://opensource.org/licenses/MIT MIT
 */
interface ToolInterface
{
    /**
     * @return string
     */
    public function niceName();

    /**
     * @return string
     */
    public function packageName();

    /**
     * @return string
     */
    public function pharUrl();

    /**
     * @param Paths $paths
     * @return string
     */
    public function pharTarget(Paths $paths);

    /**
     * @param string $packageVendorPath
     * @return string
     */
    public function executableFile($packageVendorPath);

    /**
     * @return string
     */
    public function minVersion();

    /**
     * @return callable
     */
    public function postPharChecker();

    /**
     * @param string $command
     * @param Paths $paths
     * @param IO $io
     * @return string
     */
    public function prepareCommand($command, Paths $paths, IO $io);

}