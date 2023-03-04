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
use WeCodeMore\WpStarter\Cli\PhpProcess;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Io\Io;

class PhpProcessTest extends IntegrationTestCase
{
    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpProcess
     */
    public function testExecuteSimple(): void
    {
        $process = $this->factoryPhpProcess();

        static::assertTrue($process->execute('-r "echo \'I ran!\';"'));
        static::assertStringContainsString('I ran!', $this->collectOutput());
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpProcess
     */
    public function testExecuteWithEnvironment(): void
    {
        $process = $this->factoryPhpProcess()->withEnvironment(['FOO' => 'I ran with env!']);

        static::assertTrue($process->execute('-r "echo getenv(\'FOO\');"'));
        static::assertStringContainsString('I ran with env!', $this->collectOutput());
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpProcess
     */
    public function testExecuteSilently(): void
    {
        $process = $this->factoryPhpProcess();

        static::assertTrue($process->executeSilently('-r "echo \'la la la\';"'));
        static::assertSame('', trim($this->collectOutput()));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\PhpProcess
     */
    public function testExecuteAndFail(): void
    {
        $process = $this->factoryPhpProcess();

        static::assertFalse($process->execute('-r "exit(1);"'));
        static::assertTrue($process->execute('-r "exit();"'));
        static::assertFalse($process->execute('-r "throw new \Exception(\'Failed!\');"'));
        // Let's make sure current process is not affected...
        static::assertTrue($process->execute('-r "echo \'I ran!\';"'));
        static::assertStringContainsString('I ran!', $this->collectOutput());
    }
}
