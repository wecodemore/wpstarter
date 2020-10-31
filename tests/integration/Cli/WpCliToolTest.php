<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Integration\Cli;

use Composer\Util\Filesystem;
use org\bovigo\vfs\vfsStream;
use WeCodeMore\WpStarter\Cli\WpCliTool;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Config\Validator;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Io\Io;

class WpCliToolTest extends IntegrationTestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\WpCliTool
     */
    public function testTargetPathsFindsDefault()
    {
        $dir = vfsStream::setup('directory');
        $root = $dir->url();
        touch("{$root}/wp-cli.phar");

        $tool = $this->factoryTool($root);

        static::assertSame("{$root}/wp-cli.phar", $tool->pharTarget($this->createPaths($root)));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\WpCliTool
     */
    public function testTargetPathsFindsFileNamedAsUrl()
    {
        $dir = vfsStream::setup('directory');
        $root = $dir->url();

        $tool = $this->factoryTool($root);

        $name = basename($tool->pharUrl());

        touch("{$root}/wp-cli-2.3.phar");
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

        $tool = $this->factoryTool($root);

        $paths = $this->createPaths($root);

        touch("{$root}/wp-cli-1.0.phar");

        static::assertSame("{$root}/wp-cli.phar", $tool->pharTarget($paths));

        touch("{$root}/wp-cli-2.5.phar");

        static::assertSame("{$root}/wp-cli-2.5.phar", $tool->pharTarget($paths));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\WpCliTool
     */
    public function testCheckPharSuccess()
    {
        $path = getenv('TESTS_FIXTURES_PATH') . '/wp-cli-2.4.0.phar';
        $tool = $this->factoryTool();

        static::assertTrue($tool->checkPhar($path, new Io($this->createComposerIo())));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\WpCliTool
     */
    public function testCheckPharError()
    {
        $path = getenv('TESTS_FIXTURES_PATH') . '/wp-cli-2.0.1.phar';
        $tool = $this->factoryTool();

        static::assertFalse($tool->checkPhar($path, new Io($this->createComposerIo())));
    }

    /**
     * @param string|null $cwd
     * @return WpCliTool
     */
    private function factoryTool(string $cwd = null): WpCliTool
    {
        return new WpCliTool(
            new Config(
                [Config::INSTALL_WP_CLI => true],
                new Validator($this->createPaths($cwd), new Filesystem())
            ),
            $this->createUrlDownloader(),
            new Io($this->createComposerIo())
        );
    }
}
