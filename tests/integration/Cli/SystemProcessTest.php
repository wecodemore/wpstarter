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
use WeCodeMore\WpStarter\Cli\SystemProcess;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Io\Io;

class SystemProcessTest extends IntegrationTestCase
{
    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\SystemProcess
     */
    public function testExecute(): void
    {
        $process = $this->factorySystemProcess()->withEnvironment(['FOO' => 'I ran with env!']);

        $php = (new PhpExecutableFinder())->find();

        static::assertTrue($process->execute($php . ' -r "echo getenv(\'FOO\');"'));
        static::assertStringContainsString('I ran with env!', $this->collectOutput());
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Cli\SystemProcess
     */
    public function testExecuteSilently(): void
    {
        $process = $this->factorySystemProcess();

        $php = (new PhpExecutableFinder())->find();

        static::assertTrue($process->executeSilently($php . ' -r "echo \'la la la\';"'));
        static::assertSame('', trim($this->collectOutput()));
    }
}
