<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Integration\Cli;

use Composer\Factory;
use Composer\Util\Filesystem;
use org\bovigo\vfs\vfsStream;
use WeCodeMore\WpStarter\Cli\WpCliTool;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Config\Validator;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\UrlDownloader;

class WpCliToolTest extends IntegrationTestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }

    /**
     * @param UrlDownloader|null $urlDownloader
     * @param string|null $cwd
     * @return WpCliTool
     */
    private function createTool(UrlDownloader $urlDownloader = null, string $cwd = null): WpCliTool
    {
        $config = new Config(
            [Config::INSTALL_WP_CLI => true],
            new Validator($this->createPaths($cwd), new Filesystem())
        );

        $urlDownloader or $urlDownloader = new UrlDownloader(
            new Filesystem(),
            Factory::createRemoteFilesystem(
                $this->createComposerIo(),
                $this->createComposerConfig()
            )
        );

        return new WpCliTool(
            $config,
            $urlDownloader,
            new Io($this->createComposerIo())
        );
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\WpCliTool
     */
    public function testCheckPhar()
    {
        $tool = $this->createTool();

        $url = $tool->pharUrl();

        $dir = vfsStream::setup('directory');
        $targetFile = $dir->url() . '/' . basename($url);

        $this->downloadWpCliPhar($url, $targetFile);

        $checked = $tool->checkPhar($targetFile, new Io($this->createComposerIo()));

        static::assertTrue($checked);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\WpCliTool
     */
    public function testPharHashTestFailure()
    {
        $urlDownloader = \Mockery::mock(UrlDownloader::class);
        $urlDownloader->makePartial();
        $urlDownloader
            ->shouldReceive('fetch')
            ->once()
            ->with(\Mockery::type('string'))
            ->andReturnUsing(
                function (string $url): string {
                    $ext = pathinfo(basename($url), PATHINFO_EXTENSION);

                    static::assertTrue(in_array($ext, ['md5', 'sha512'], true));

                    $len = $ext === 'md5' ? 22 : 96;

                    return base64_encode(random_bytes($len));
                }
            );

        $tool = $this->createTool($urlDownloader);

        $url = $tool->pharUrl();

        $dir = vfsStream::setup('directory');
        $targetFile = $dir->url() . '/' . basename($url);

        $this->downloadWpCliPhar($url, $targetFile);

        static::assertFalse($tool->checkPhar($targetFile, new Io($this->createComposerIo())));
        static::assertContains('hash check failed', $this->collectOutput());
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\WpCliTool
     */
    public function testPharHashDownloadFailure()
    {
        $urlDownloader = \Mockery::mock(UrlDownloader::class);
        $urlDownloader->makePartial();
        $urlDownloader
            ->shouldReceive('fetch')
            ->once()
            ->with(\Mockery::type('string'))
            ->andReturn('');

        $tool = $this->createTool($urlDownloader);

        $url = $tool->pharUrl();

        $dir = vfsStream::setup('directory');
        $targetFile = $dir->url() . '/' . basename($url);

        $this->downloadWpCliPhar($url, $targetFile);

        static::assertFalse($tool->checkPhar($targetFile, new Io($this->createComposerIo())));
        static::assertContains('Failed to download', $this->collectOutput());
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\WpCliTool
     */
    public function testTargetPathsFindsDefault()
    {
        $dir = vfsStream::setup('directory');
        $root = $dir->url();
        touch("{$root}/wp-cli.phar");

        $tool = $this->createTool(null, $root);

        static::assertSame("{$root}/wp-cli.phar", $tool->pharTarget($this->createPaths($root)));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\WpCliTool
     */
    public function testTargetPathsFindsFileNamedAsUrl()
    {
        $dir = vfsStream::setup('directory');
        $root = $dir->url();

        $tool = $this->createTool(null, $root);

        $name = basename($tool->pharUrl());

        touch("{$root}/wp-cli-3.0.phar");
        touch("{$root}/wp-cli-1.0.phar");
        touch("{$root}/{$name}");

        static::assertSame("{$root}/{$name}", $tool->pharTarget($this->createPaths($root)));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\WpCliTool
     */
    public function testFilesIgnoresFilesForOldVersions()
    {
        $dir = vfsStream::setup('directory');
        $root = $dir->url();

        $tool = $this->createTool(null, $root);

        $paths = $this->createPaths($root);

        touch("{$root}/wp-cli-1.0.phar");

        static::assertSame("{$root}/wp-cli.phar", $tool->pharTarget($paths));

        touch("{$root}/wp-cli-2.1.phar");

        static::assertSame("{$root}/wp-cli-2.1.phar", $tool->pharTarget($paths));
    }

    /**
     * @param string $url
     * @param string $targetFile
     */
    private function downloadWpCliPhar(string $url, string $targetFile)
    {
        $downloader = Factory::createRemoteFilesystem(
            $this->createComposerIo(),
            $this->createComposerConfig()
        );

        $downloader->copy(parse_url($url, PHP_URL_HOST), $url, $targetFile);
        if (!file_exists($targetFile)) {
            $this->markTestSkipped("Cannot complete test because download of {$url} failed.");
        }
    }
}
