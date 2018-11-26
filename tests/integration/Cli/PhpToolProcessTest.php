<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Integration\Cli;

use Symfony\Component\Process\PhpExecutableFinder;
use WeCodeMore\WpStarter\Cli\PhpToolProcess;
use WeCodeMore\WpStarter\Tests\DummyPhpTool;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Util\Io;

class PhpToolProcessTest extends IntegrationTestCase
{
    /**
     * @return PhpToolProcess
     */
    private function createPhpToolProcess(): PhpToolProcess
    {
        return new PhpToolProcess(
            (new PhpExecutableFinder())->find() ?: '',
            new DummyPhpTool(),
            '',
            $this->createPaths(),
            new Io($this->createComposerIo())
        );
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\PhpToolProcess
     *
     * @see \WeCodeMore\WpStarter\Tests\DummyPhpTool::prepareCommand
     */
    public function testExecute()
    {
        $process = $this->createPhpToolProcess()->withEnvironment(['XX' => 'I ran tool with env!']);

        static::assertTrue($process->execute('-r "echo getenv(\'XX\');"'));

        $output = $this->collectOutput();

        static::assertContains("Dummy!\n", $output);
        static::assertContains('I ran tool with env!', $output);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Cli\PhpToolProcess
     *
     * @see \WeCodeMore\WpStarter\Tests\DummyPhpTool::prepareCommand
     */
    public function testExecuteSilently()
    {
        $process = $this->createPhpToolProcess()->withEnvironment(['XX' => 'I ran tool with env!']);

        static::assertTrue($process->executeSilently('-r "echo getenv(\'XX\');"'));
        static::assertSame('Dummy!', trim($this->collectOutput()));
    }
}
