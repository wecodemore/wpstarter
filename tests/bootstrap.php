<?php
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

$testsDir = str_replace('\\', '/', __DIR__);
$libDir = dirname($testsDir);
$vendorDir = "{$libDir}/vendor";
$autoload = "{$vendorDir}/autoload.php";

if (!is_file($autoload)) {
    die('Please install via Composer before running tests.');
}

putenv('TESTS_PATH=' . $testsDir);
putenv('PACKAGE_PATH=' . $libDir);
putenv('VENDOR_DIR=' . $vendorDir);
putenv('TESTS_FIXTURES_PATH=' . "{$testsDir}/fixtures");

error_reporting(E_ALL); // phpcs:ignore

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', $autoload);
    require_once $autoload;
}

unset($testsDir, $libDir, $vendorDir, $autoload);
