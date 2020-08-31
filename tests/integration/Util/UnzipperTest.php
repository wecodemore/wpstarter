<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Integration\Util;

use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Util\Unzipper;

class UnzipperTest extends IntegrationTestCase
{
    /**
     * @covers \WeCodeMore\WpStarter\Util\Unzipper
     */
    public function testUnzipFail()
    {
        $dir = getenv('TESTS_FIXTURES_PATH');
        $source = $dir . '/faulty-zip.zip';
        $unzipper = new Unzipper($this->createComposerIo(), $this->createComposerConfig());

        static::assertFalse($unzipper->unzip($source, $dir));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Util\Unzipper
     */
    public function testUnzipSuccess()
    {
        $dir = getenv('TESTS_FIXTURES_PATH');
        $unzipper = new Unzipper($this->createComposerIo(), $this->createComposerConfig());

        try {
            static::assertTrue($unzipper->unzip("{$dir}/good-zip.zip", $dir));
            // this is the file contained in `good-zip.zip` file
            static::assertTrue(file_exists("{$dir}/some-zip-content.txt"));
        } finally {
            @unlink("{$dir}/some-zip-content.txt");
        }
    }
}
