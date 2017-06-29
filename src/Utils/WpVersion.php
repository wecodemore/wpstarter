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
use Composer\IO\IOInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WeCodeMore\WpStarter
 * @license http://opensource.org/licenses/MIT MIT
 */
class WpVersion
{

    const WP_PACKAGE_TYPE = 'wordpress-core';
    const MIN_WP_VERSION = '4.7';

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Go through installed packages to find WordPress version.
     * Normalize to always be in the form x.x.x
     *
     * @return string
     */
    public function discover()
    {
        /** @var array $packages */
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $vers = [];
        while (!empty($packages) && count($vers) < 2) {
            /** @var \Composer\Package\PackageInterface $package */
            $package = array_pop($packages);
            $package->getType() === self::WP_PACKAGE_TYPE and $vers[] = $package->getVersion();
        }

        if (!$vers) {
            return '';
        }

        if (count($vers) > 1) {
            $this->io->writeError([
                'Seems that more WordPress core packages are provided.',
                'WP Starter only support a single WordPress core package.',
                'WP Starter will NOT work.',
            ]);

            return '';
        }

        $version = $this->normalize($vers[0]);

        if (! $this->check($version)) {
            return '';
        }

        return $version;
    }

    /**
     * @param string $version
     * @return bool
     */
    private function check($version)
    {
        if (!is_string($version) || !$version || $version === '0.0.0') {
            return false;
        }

        return version_compare($version, self::MIN_WP_VERSION) >= 0;
    }

    /**
     * @param string $version
     * @return string
     */
    private function normalize($version)
    {
        $matched = preg_match('~^([0-9]{1,2}(?:[0-9\.]+)?)?~', $version, $matches);

        if (!$matched) {
            return '0.0.0';
        }

        $numbers = explode('.', trim($matches[1], '.'));

        return implode('.', array_replace(['0', '0', '0'], array_slice($numbers, 0, 3)));
    }

}