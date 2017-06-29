<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Utils;

use Composer\Composer;


/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WeCodeMore\WpStarter
 * @license http://opensource.org/licenses/MIT MIT
 */
class MuPluginList
{

    /**
     * @var Composer
     */
    private $composer;

    /**
     * MuPluginList constructor.
     * @param Composer $composer
     */
    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    /**
     * @return array
     */
    public function pluginList()
    {
        $list = [];
        /** @var \Composer\Package\PackageInterface[] $packages */
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $installationManager = $this->composer->getInstallationManager();
        foreach ($packages as $package) {
            if ($package->getType() === 'wordpress-muplugin') {
                $folder = rtrim($installationManager->getInstallPath($package), '/\\');
                $files = glob("{$folder}/*.php");
                count($files) === 1 and $list[] = reset($files);
            }
        }

        return $list;
    }

}