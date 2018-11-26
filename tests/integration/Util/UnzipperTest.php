<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Integration\Util;

use org\bovigo\vfs\vfsStream;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Util\Unzipper;

class UnzipperTest extends IntegrationTestCase
{
    private function createUnzipper(): Unzipper
    {
        return new Unzipper($this->createComposerIo(), $this->createComposerConfig());
    }

    /**
     * @covers \WeCodeMore\WpStarter\Util\Unzipper
     */
    public function testUnzipFail()
    {
        $source = getenv('TESTS_FIXTURES_PATH') . '/faulty-zip.zip';

        $dir = vfsStream::setup('directory');
        $targetDir = $dir->url();

        $unzipper = $this->createUnzipper();

        static::assertFalse($unzipper->unzip($source, $targetDir));
        static::assertFalse($dir->hasChildren());
    }

    /**
     * @covers \WeCodeMore\WpStarter\Util\Unzipper
     */
    public function testUnzipSuccess()
    {
        $source = getenv('TESTS_FIXTURES_PATH') . '/good-zip.zip';

        $dir = vfsStream::setup('directory');
        $targetDir = $dir->url();

        $unzipper = $this->createUnzipper();

        static::assertTrue($unzipper->unzip($source, $targetDir));
        static::assertTrue($dir->hasChildren());
    }
}