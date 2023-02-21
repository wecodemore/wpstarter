<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Integration\Cli;

use Composer\Util\Filesystem;
use org\bovigo\vfs\vfsStream;
use WeCodeMore\WpStarter\Cli\PharInstaller;
use WeCodeMore\WpStarter\Cli\PhpToolProcess;
use WeCodeMore\WpStarter\Cli\PhpToolProcessFactory;
use WeCodeMore\WpStarter\Cli\WpCliTool;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Config\Validator;
use WeCodeMore\WpStarter\Tests\DummyPhpTool;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\PackageFinder;
use WeCodeMore\WpStarter\Util\UrlDownloader;

class PhpToolProcessFactoryTest extends IntegrationTestCase
{
    /**
     * @after
     */
    protected function after(): void
    {
        parent::tearDown();
        \Mockery::close();
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpToolProcessFactory
     */
    public function testCreateFailsIfNoPackageNoPharPathAndNoPharUrl(): void
    {
        $factory = $this->factoryPhpToolProcessFactory();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMsgRegex('/^Failed installation/');

        $factory->create(new DummyPhpTool());
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpToolProcessFactory
     */
    public function testCreateViaPackageFailIfMinVersionTooLow(): void
    {
        $factory = $this->factoryPhpToolProcessFactory();

        $tool = new DummyPhpTool();
        $tool->packageName = 'symfony/dotenv';
        $tool->minVersion = '9999.9999';

        $this->expectException(\RuntimeException::class);

        $factory->create($tool);

        static::assertStringContainsString(
            'lower than minimum required 9999.9999',
            $this->collectOutput()
        );
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpToolProcessFactory
     */
    public function testCreateViaPackage(): void
    {
        $factory = $this->factoryPhpToolProcessFactory();

        $tool = new DummyPhpTool();
        $tool->packageName = 'symfony/dotenv';
        $tool->minVersion = '0.1';

        $this->assertProcessWorks($factory->create($tool));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpToolProcessFactory
     */
    public function testCreateViaPharPath(): void
    {
        $factory = $this->factoryPhpToolProcessFactory();

        $tool = new DummyPhpTool();
        $tool->pharTarget = __FILE__;

        $this->assertProcessWorks($factory->create($tool));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpToolProcessFactory
     */
    public function testCreateViaPharUrl(): void
    {
        $url = 'https://example.com/downloads?file=some-phar';
        $dir = vfsStream::setup('directory');
        $path = $dir->url() . '/some-phar.phar';

        $urlDownloader = \Mockery::mock(UrlDownloader::class);
        $urlDownloader->makePartial();
        $urlDownloader->shouldReceive('save')
            ->once()
            ->with($url, $path)
            ->andReturnUsing(static function (string $url, string $path): bool {
                $url and touch($path);
                return true;
            });

        $factory = $this->factoryPhpToolProcessFactory($urlDownloader);

        $tool = new DummyPhpTool();
        $tool->pharTarget = $path;
        $tool->pharUrl = $url;
        $tool->pharIsValid = true;

        $process = $factory->create($tool);

        static::assertStringContainsString('Installing ', $this->collectOutput());

        $this->assertProcessWorks($process);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpToolProcessFactory
     */
    public function testCreateViaPharUrlFailsWhenPharCheckFails(): void
    {
        $url = 'https://example.com/downloads?file=some-phar';
        $dir = vfsStream::setup('directory');
        $path = $dir->url() . '/some-phar.phar';

        $urlDownloader = \Mockery::mock(UrlDownloader::class);
        $urlDownloader->makePartial();
        $urlDownloader->shouldReceive('save')
            ->once()
            ->with($url, $path)
            ->andReturnUsing(static function (string $url, string $path): bool {
                $url and touch($path);
                return true;
            });

        $factory = $this->factoryPhpToolProcessFactory($urlDownloader);

        $tool = new DummyPhpTool();
        $tool->pharTarget = $path;
        $tool->pharUrl = $url;
        $tool->pharIsValid = false;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMsgRegex('/Failed phar download/');

        $factory->create($tool);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpToolProcessFactory
     */
    public function testRunWpCliCommandViaFileSystemBootstrap(): void
    {
        $config = new Config(
            [Config::INSTALL_WP_CLI => false],
            new Validator($this->createPaths(), new Filesystem())
        );

        $tool = new WpCliTool(
            $config,
            $this->createUrlDownloader(),
            new Io($this->createComposerIo())
        );

        $factory = $this->factoryPhpToolProcessFactory();

        $process = $factory->create($tool);

        $process->execute('cli version');

        static::assertStringMatchesRegex('/^WP-CLI [0-9\.]+$/', trim($this->collectOutput()));
    }

    /**
     * @param PhpToolProcess $process
     */
    private function assertProcessWorks(PhpToolProcess $process): void
    {
        static::assertTrue($process->execute('-r "echo \'Hi!!!\';"'));

        $output = trim($this->collectOutput());

        static::assertStringStartsWith('Dummy!', $output);
        static::assertStringEndsWith('Hi!!!', $output);
    }

    /**
     * @param UrlDownloader|null $urlDownloader
     * @return PhpToolProcessFactory
     */
    private function factoryPhpToolProcessFactory(
        UrlDownloader $urlDownloader = null
    ): PhpToolProcessFactory {

        $urlDownloader or $urlDownloader = $this->createUrlDownloader();

        $composer = $this->createComposer();

        return new PhpToolProcessFactory(
            $this->createPaths(),
            new Io($this->createComposerIo()),
            new PharInstaller(
                new Io($this->createComposerIo()),
                $urlDownloader
            ),
            new PackageFinder(
                $composer->getRepositoryManager()->getLocalRepository(),
                $composer->getInstallationManager(),
                new Filesystem()
            )
        );
    }
}
