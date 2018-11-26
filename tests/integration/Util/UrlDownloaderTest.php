<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Integration\Util;

use Composer\Factory;
use Composer\Util\Filesystem;
use org\bovigo\vfs\vfsStream;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Util\UrlDownloader;

class UrlDownloaderTest extends IntegrationTestCase
{
    /**
     * @covers \WeCodeMore\WpStarter\Util\UrlDownloader
     */
    private function createUrlDownloader(): UrlDownloader
    {
        return new UrlDownloader(
            new Filesystem(),
            Factory::createRemoteFilesystem(
                $this->createComposerIo(),
                $this->createComposerConfig()
            )
        );
    }

    /**
     * @covers \WeCodeMore\WpStarter\Util\UrlDownloader
     */
    public function testFetchFailsForWrongUrl()
    {
        $downloader = $this->createUrlDownloader();

        static::assertSame('', $downloader->fetch('-https://example.com'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Util\UrlDownloader
     */
    public function testFetch()
    {
        $downloader = $this->createUrlDownloader();

        $html = $downloader->fetch('https://www.w3.org/');

        static::assertContains('<html', $html);
        static::assertContains('World Wide Web', $html);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Util\UrlDownloader
     */
    public function testSave()
    {
        $downloader = $this->createUrlDownloader();

        $dir = vfsStream::setup('directory');
        $targetFile = $dir->url() . '/w3c.html';

        static::assertTrue($downloader->save('https://www.w3.org/', $targetFile));
        static::assertTrue($dir->hasChild('w3c.html'));

        $html = file_get_contents($targetFile);

        static::assertContains('<html', $html);
        static::assertContains('World Wide Web', $html);
    }
}