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
use Composer\Package\PackageInterface;

class WpVersion
{
    const WP_PACKAGE_TYPE = 'wordpress-core';
    const MIN_WP_VERSION = '4.8';

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
        // first 3 numbers, always 3 numbers (padding with zeroes if needed)
        $numbers = array_map('intval', array_replace([0, 0, 0], array_slice($numeric, 0, 3)));

        // for many years to come WP will not have 1st number  bigger than 9, and if they
        // stick with current versioning schema, second number will never be bigger than 9.
        if ($numbers[0] > 9 || $numbers[1] > 9) {
            return '';
        }

        return implode('.', $numbers);
    }

    /**
     * @param IOInterface $io
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * Go through installed packages to find WordPress version.
     *
     * Retuned found version, if any, will be normalized to `x.x.x` format.
     *
     * @param PackageInterface[] $packages
     * @return string
     */
    public function discover(PackageInterface ...$packages): string
    {
        $vers = [];
        while (!empty($packages) && count($vers) < 2) {
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
                    "{$red}  It seems that two or more WP core packages are required.   </>",
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
