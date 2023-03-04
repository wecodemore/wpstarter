<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Integration\Cli;

use Symfony\Component\Process\PhpExecutableFinder;
use WeCodeMore\WpStarter\Cli\PhpToolProcess;
use WeCodeMore\WpStarter\Tests\DummyPhpTool;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Io\Io;

class PhpToolProcessTest extends IntegrationTestCase
{
    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpToolProcess
     * @see \WeCodeMore\WpStarter\Tests\DummyPhpTool::prepareCommand
     */
    public function testExecute(): void
    {
        $process = $this->factoryPhpToolProcess()
            ->withEnvironment(['XX' => 'I ran tool with env!']);

        static::assertTrue($process->execute('-r "echo getenv(\'XX\');"'));

        $output = $this->collectOutput();

        static::assertStringContainsString("Dummy!\n", $output);
        static::assertStringContainsString('I ran tool with env!', $output);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpToolProcess
     * @see \WeCodeMore\WpStarter\Tests\DummyPhpTool::prepareCommand
     */
    public function testExecuteSilently(): void
    {
        $process = $this->factoryPhpToolProcess()
            ->withEnvironment(['XX' => 'I ran tool with env!']);

        static::assertTrue($process->executeSilently('-r "echo getenv(\'XX\');"'));
        static::assertSame('Dummy!', trim($this->collectOutput()));
    }

    /**
     * @return PhpToolProcess
     */
    private function factoryPhpToolProcess(): PhpToolProcess
    {
        return new PhpToolProcess(
            $this->factoryPhpProcess(),
            new DummyPhpTool(),
            '',
            $this->factoryPaths(),
            new Io($this->factoryComposerIo())
        );
    }
}
