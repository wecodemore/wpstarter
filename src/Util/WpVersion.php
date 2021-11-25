<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use WeCodeMore\WpStarter\Io\Io;

/**
 * Helper to get and normalize the version of WordPress installed via Composer.
 *
 * Installed packages are parsed to find the ones with wordpress-core package type.
 * An error is raised in case of more packages found.
 */
class WpVersion
{
    public const WP_PACKAGE_TYPE = 'wordpress-core';
    public const MIN_WP_VERSION = '4.8';

    /**
     * @var PackageFinder
     */
    private $packageFinder;

    /**
     * @var Io
     */
    private $io;

    /**
     * @var string|null
     */
    private $fallbackVersion;

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

        return implode('.', $numbers);
    }

    /**
     * @param PackageFinder $packageFinder
     * @param Io $io
     * @param string|null $fallbackVersion
     */
    public function __construct(
        PackageFinder $packageFinder,
        Io $io,
        string $fallbackVersion = null
    ) {

        $this->packageFinder = $packageFinder;
        $this->io = $io;
        $this->fallbackVersion = $fallbackVersion;
    }

    /**
     * Go through installed packages to find version of installed WordPress core package.
     *
     * Returned found version, if any, will be normalized to `x.x.x` format.
     *
     * @return string
     */
    public function discover(): string
    {
        $versions = [];
        $packages = $this->packageFinder->findByType(self::WP_PACKAGE_TYPE);
        foreach ($packages as $package) {
            $versions[] = $package->getVersion();
            if (count($versions) > 1) {
                break;
            }
        }

        if (!$versions) {
            return $this->bail('no-wp');
        }

        if (count($versions) > 1) {
            return $this->bail('more-wp');
        }

        $fallback = $this->fallbackVersion ? static::normalize($this->fallbackVersion) : null;
        $version = static::normalize(reset($versions)) ?: $fallback;

        if (!$version) {
            return $this->bail('invalid-wp');
        }

        if (!version_compare($version, self::MIN_WP_VERSION, '>=')) {
            $version = '';
            $min = self::MIN_WP_VERSION;
            $this->io->writeErrorBlock(
                "Installed WP version {$version} is lower than minimum required {$min}.",
                'WP Starter failed.'
            );
        }

        return $version;
    }

    /**
     * @param string $reason
     * @return string
     */
    private function bail(string $reason): string
    {
        $lines = [];

        switch ($reason) {
            case 'no-wp':
                $lines = [
                    'No WordPress version found.',
                    'To skip WP requirement check, set \'require-wp\' to false in WP Starter '
                    . 'configuration in composer.json.',
                ];
                break;
            case 'more-wp':
                $lines = [
                    'It seems that two or more WP core packages are required.',
                    'WP Starter only supports a single WordPress core package.',
                ];
                break;
            case 'dev-wp':
                $lines = [
                    'WordPress core package is required as dev package.',
                    'WP Starter can\'t work with that unless a numeric version is configured in '
                    . 'composer.json via \'wp-version\' setting.',
                ];
                break;
            case 'invalid-wp':
                $lines = [
                    'No valid WP version was found.',
                ];
                break;
        }

        $lines[] = 'WP Starter failed.';

        $this->io->writeErrorBlock(...$lines);

        return '';
    }
}
