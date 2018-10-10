<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Composer;
use Composer\IO\IOInterface;

class WpVersion
{
    const WP_PACKAGE_TYPE = 'wordpress-core';
    const MIN_WP_VERSION = '4.8';

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @param string $version
     * @return string
     */
    public static function normalize(string $version): string
    {
        $pattern = '~^(?P<numbers>(?:[0-9]+)+(?:[0-9\.]+)?)+(?P<anything>.*?)?$~';
        $matched = preg_match($pattern, $version, $matches);

        if (!$matched) {
            return '';
        }

        $numeric = explode('.', trim($matches['numbers'], '.'));
        $numbers = array_map('intval', array_replace([0, 0, 0], array_slice($numeric, 0, 3)));

        if ($numbers[0] > 9 || $numbers[1] > 9) { // [0] beacuse will take years, [1] is WP special
            return '';
        }

        return implode('.', $numbers);
    }

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
    public function discover(): string
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
            $red = '<bg=red;fg=white;option=bold>  ';
            $this->io->writeError(
                [
                    "{$red}  Seems that more WordPress core packages are provided.      </>",
                    "{$red}  WP Starter only supports a single WordPress core package.  </>",
                    "{$red}  WP Starter will NOT work.                                  </>",
                ]
            );

            return '';
        }

        $version = static::normalize((string)$vers[0]);

        if (!$version || !version_compare($version, self::MIN_WP_VERSION, '>=')) {
            return '';
        }

        return $version;
    }
}
