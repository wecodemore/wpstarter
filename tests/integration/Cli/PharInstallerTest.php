<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Integration\Cli;

use Composer;
use PHPUnit\Framework\Exception;
use WeCodeMore\WpStarter\Cli\PharInstaller;
use WeCodeMore\WpStarter\Cli\PhpTool;
use WeCodeMore\WpStarter\Cli\WpCliTool;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Config\Validator;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Tests\DummyPhpTool;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Util\UrlDownloader;
use org\bovigo\vfs\vfsStream;

class PharInstallerTest extends IntegrationTestCase
{
    /**
     * @return PharInstaller
     */
    private function createPharInstaller(): PharInstaller
    {
        return new PharInstaller(
            new Io($this->createComposerIo()),
            $this->createUrlDownloader()
        );
    }

    /**
     * @return UrlDownloader
     */
    private function createUrlDownloader(): UrlDownloader
    {
        return new UrlDownloader(
            new Composer\Util\Filesystem(),
            Composer\Factory::createRemoteFilesystem(
                $this->createComposerIo(),
                $this->createComposerConfig()
            )
        );
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\PharInstaller
     */
    public function testInstallationOfWpCliPharFailIfConfigDisablesIt()
    {
        $config = new Config(
            [Config::INSTALL_WP_CLI => false],
            new Validator($this->createPaths(), new Composer\Util\Filesystem())
        );

        $tool = new WpCliTool(
            $config,
            $this->createUrlDownloader(),
            new Io($this->createComposerIo())
        );

        $dir = vfsStream::setup('directory');
        $targetFile = $dir->url() . '/wp-cli.phar';

        $installer = $this->createPharInstaller();
        $installer->install($tool, $targetFile);

        static::assertStringStartsWith('Skipping ', ltrim($this->collectOutput()));
        static::assertFalse($dir->hasChild('wp-cli.phar'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\PharInstaller
     */
    public function testInstallationOfWpCliPharWorks()
    {
        $config = new Config(
            [Config::INSTALL_WP_CLI => true],
            new Validator($this->createPaths(), new Composer\Util\Filesystem())
        );

        $tool = new WpCliTool(
            $config,
            $this->createUrlDownloader(),
            new Io($this->createComposerIo())
        );

        $dir = vfsStream::setup('directory');
        $targetFile = $dir->url() . '/wp-cli.phar';

        $installer = $this->createPharInstaller();
        $installed = $installer->install($tool, $targetFile);

        $output = ltrim($this->collectOutput());

        static::assertStringStartsWith('Installing ', $output);
        static::assertContains('installed successfully', $output);
        static::assertTrue($dir->hasChild('wp-cli.phar'));
        static::assertSame($targetFile, $installed);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\PharInstaller
     */
    public function testInstallationOfNonExistingPharFails()
    {
        $config = new Config(
            [Config::INSTALL_WP_CLI => true],
            new Validator($this->createPaths(), new Composer\Util\Filesystem())
        );

        $wpCliTool = new WpCliTool(
            $config,
            $this->createUrlDownloader(),
            new Io($this->createComposerIo())
        );

        $tool = new class($wpCliTool) extends DummyPhpTool implements PhpTool {

            private $tool;

            public function __construct(WpCliTool $tool)
            {
                $this->tool = $tool;
                $this->pharUrl = $tool->pharUrl();
            }

            public function checkPhar(string $pharPath, Io $io): bool
            {
                if (!$this->tool->checkPhar($pharPath, $io)) {
                    throw new Exception('Checksum check failed');
                }

                return false;
            }
        };

        $dir = vfsStream::setup('directory');
        $targetFile = $dir->url() . '/tool.phar';

        $installer = $this->createPharInstaller();
        $installed = $installer->install($tool, $targetFile);

        $output = ltrim($this->collectOutput());

        static::assertContains('Installing ', $output);
        static::assertFalse($dir->hasChild('wp-cli.phar'));
        static::assertSame('', $installed);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\PharInstaller
     */
    public function testInstallationFailsIfChecksumFails()
    {
        $tool = new DummyPhpTool();
        $tool->pharUrl = 'https://example.example/' . base64_encode(random_bytes(3));

        $dir = vfsStream::setup('directory');
        $targetFile = $dir->url() . '/tool.phar';

        $installer = $this->createPharInstaller();
        $installed = $installer->install($tool, $targetFile);

        $output = ltrim($this->collectOutput());

        static::assertStringStartsWith('Installing ', $output);
        static::assertContains('Failed to download', $output);
        static::assertFalse($dir->hasChild('wp-cli.phar'));
        static::assertSame('', $installed);
    }
}
