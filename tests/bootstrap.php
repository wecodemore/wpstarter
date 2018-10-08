<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$vendor = dirname(__FILE__, 2).'/vendor/';

if (!realpath($vendor)) {
    die('Please install via Composer before running tests.');
}

require_once $vendor.'autoload.php';
unset($vendor);

putenv('TESTS_BASEPATH='.__DIR__);
putenv('TESTS_BASEPATH_UNIT='.__DIR__.'/unit');
putenv('TESTS_FIXTURES_PATH='.__DIR__.'/fixtures');
