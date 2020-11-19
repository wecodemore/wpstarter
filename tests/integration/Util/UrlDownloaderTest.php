<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Integration\Util;

use WeCodeMore\WpStarter\Tests\IntegrationTestCase;

class UrlDownloaderTest extends IntegrationTestCase
{
    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Util\UrlDownloader
     */
    public function testFetchFailsForWrongUrl()
    {
        $downloader = $this->createUrlDownloader();

        static::assertSame('', $downloader->fetch('-https://example.com'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Util\UrlDownloader
     */
    public function testFetch()
    {
        $downloader = $this->createUrlDownloader();

        $html = $downloader->fetch('https://www.w3.org/');

        static::assertStringContainsString('<html', $html);
        static::assertStringContainsString('World Wide Web', $html);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Util\UrlDownloader
     */
    public function testSave()
    {
        $downloader = $this->createUrlDownloader();

        $targetFile = getenv('TESTS_FIXTURES_PATH') . '/w3c.html';
        if (file_exists($targetFile)) {
            @unlink($targetFile);
            if (file_exists($targetFile)) {
                $this->markTestSkipped("Could not delete {$targetFile}.");
            }
        }

        static::assertTrue($downloader->save('https://www.w3.org/', $targetFile));
        static::assertTrue(file_exists($targetFile));

        $html = file_get_contents($targetFile);
        @unlink($targetFile);

        static::assertStringContainsString('<html', $html);
        static::assertStringContainsString('World Wide Web', $html);
    }
}
