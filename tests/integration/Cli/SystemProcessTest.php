<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Integration\Cli;

use Symfony\Component\Process\PhpExecutableFinder;
use WeCodeMore\WpStarter\Cli\SystemProcess;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Io\Io;

class SystemProcessTest extends IntegrationTestCase
{
    /**
     * @return SystemProcess
     */
    private function createSystemProcess(): SystemProcess
    {
        return new SystemProcess(
            $this->createPaths(),
            new Io($this->createComposerIo())
        );
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\SystemProcess
     */
    public function testExecute()
    {
        $process = $this->createSystemProcess()->withEnvironment(['FOO' => 'I ran with env!']);

        $php = (new PhpExecutableFinder())->find();

        static::assertTrue($process->execute($php . ' -r "echo getenv(\'FOO\');"'));
        static::assertStringContainsString('I ran with env!', $this->collectOutput());
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\SystemProcess
     */
    public function testExecuteSilently()
    {
        $process = $this->createSystemProcess();

        $php = (new PhpExecutableFinder())->find();

        static::assertTrue($process->executeSilently($php . ' -r "echo \'la la la\';"'));
        static::assertSame('', trim($this->collectOutput()));
    }
}
