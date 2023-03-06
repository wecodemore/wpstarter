<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests;

use Composer;
use Composer\Factory;
use Composer\Util\Filesystem as ComposerFilesystem;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use WeCodeMore\WpStarter\Cli\PhpProcess;
use WeCodeMore\WpStarter\Cli\SystemProcess;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\Filesystem;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\UrlDownloader;

abstract class IntegrationTestCase extends \PHPUnit\Framework\TestCase
{
    use PhpUnitCrossVersion;

    /**
     * @var OutputInterface[]
     */
    private $outputs = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->outputs = [];
    }

    /**
     * @param int $verbosity
     * @return string
     */
    protected function collectOutput(int $verbosity = OutputInterface::VERBOSITY_NORMAL): string
    {
        if ($this->outputs[$verbosity] ?? null) {
            $output = $this->outputs[$verbosity]->output;
            $this->outputs[$verbosity]->output = '';

            return $output;
        }

        return '';
    }

    /**
     * @param int $verbosity
     * @return OutputInterface
     */
    protected function factoryConsoleOutput(
        int $verbosity = OutputInterface::VERBOSITY_NORMAL
    ): OutputInterface {

        if ($this->outputs[$verbosity] ?? null) {
            return $this->outputs[$verbosity];
        }

        $formatter = new OutputFormatter(false, Composer\Factory::createAdditionalStyles());

        if (PHP_VERSION_ID < 702000) {
            $this->outputs[$verbosity] = new class($verbosity, false, $formatter) extends Output
            {
                public $output = '';
                public $lines = [];

                /** @noinspection PhpSignatureMismatchDuringInheritanceInspection */
                protected function doWrite($message, $newline)
                {
                    if (!$newline && $this->lines) {
                        $last = array_pop($this->lines);
                        $message = $last . $message;
                    }

                    $this->lines[] = $message;
                    $this->output = implode("\n", $this->lines);
                }
            };

            return $this->outputs[$verbosity];
        }

        $this->outputs[$verbosity] = new class($verbosity, false, $formatter) extends Output
        {
            public $output = '';
            public $lines = [];

            protected function doWrite(string $message, bool $newline)
            {
                if (!$newline && $this->lines) {
                    $last = array_pop($this->lines);
                    $message = $last . $message;
                }

                $this->lines[] = $message;
                $this->output = implode("\n", $this->lines);
            }
        };

        return $this->outputs[$verbosity];
    }

    /**
     * @param string $input
     * @param int $verbosity
     * @return Composer\IO\IOInterface
     */
    protected function factoryComposerIo(
        string $input = '',
        int $verbosity = OutputInterface::VERBOSITY_NORMAL
    ): Composer\IO\IOInterface {

        return new Composer\IO\ConsoleIO(
            new StringInput($input),
            $this->factoryConsoleOutput($verbosity),
            new HelperSet()
        );
    }

    /**
     * @param string|null $cwd
     * @param int $verbosity
     * @param string $input
     * @param array $extra
     * @return Paths
     */
    protected function factoryPaths(
        string $cwd = null,
        int $verbosity = OutputInterface::VERBOSITY_NORMAL,
        string $input = '',
        array $extra = []
    ): Paths {

        return $cwd
            ? Paths::withRoot(
                $cwd,
                $this->factoryComposerConfig($input, $verbosity, $cwd),
                $extra,
                new Composer\Util\Filesystem()
            )
            : new Paths(
                $this->factoryComposerConfig($input, $verbosity, $cwd),
                $extra,
                new Composer\Util\Filesystem()
            );
    }

    /**
     * @param string $input
     * @param int $verbosity
     * @param string|null $cwd
     * @return Composer\Config
     */
    protected function factoryComposerConfig(
        string $input = '',
        int $verbosity = OutputInterface::VERBOSITY_NORMAL,
        string $cwd = null
    ): Composer\Config {

        return Composer\Factory::createConfig(
            $this->factoryComposerIo($input, $verbosity),
            $cwd ?? getenv('PACKAGE_PATH')
        );
    }

    /**
     * @return Composer\Composer
     */
    protected function factoryComposer(): Composer\Composer
    {
        $path = getenv('PACKAGE_PATH') . '/composer.json';

        return Composer\Factory::create($this->factoryComposerIo(), $path, true);
    }

    /**
     * @param string $cwd
     * @return SystemProcess
     */
    protected function factorySystemProcess(string $cwd = null): SystemProcess
    {
        return new SystemProcess(
            $this->factoryPaths($cwd),
            new Io($this->factoryComposerIo())
        );
    }

    /**
     * @param string $cwd
     * @return PhpProcess
     */
    protected function factoryPhpProcess(string $cwd = null): PhpProcess
    {
        $php = (new PhpExecutableFinder())->find() ?: 'php';

        return new PhpProcess($php, $this->factorySystemProcess($cwd));
    }

    /**
     * @return UrlDownloader
     */
    protected function factoryUrlDownloader(): UrlDownloader
    {
        $ver = Composer\Composer::RUNTIME_API_VERSION;
        if (version_compare($ver, '2', '<')) {
            /** @noinspection PhpUndefinedMethodInspection */
            return UrlDownloader::newV1(
                Factory::createRemoteFilesystem(
                    $this->factoryComposerIo(),
                    $this->factoryComposerConfig()
                ),
                new Filesystem(new ComposerFilesystem()),
                false
            );
        }

        return UrlDownloader::newV2(
            Factory::createHttpDownloader(
                $this->factoryComposerIo(),
                $this->factoryComposerConfig()
            ),
            new Filesystem(new ComposerFilesystem()),
            false
        );
    }
}
