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
    /** @var array<int, CollectingOutput> */
    private array $outputs = [];

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
        if (isset($this->outputs[$verbosity])) {
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

        if (!isset($this->outputs[$verbosity])) {
            $this->outputs[$verbosity] = new CollectingOutput($verbosity);
        }

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
     * @param array<string, mixed> $extra
     * @return Paths
     */
    protected function factoryPaths(
        ?string $cwd = null,
        int $verbosity = OutputInterface::VERBOSITY_NORMAL,
        string $input = '',
        array $extra = []
    ): Paths {

        $config = $this->factoryComposerConfig($input, $verbosity, $cwd);
        $filesystem = new Composer\Util\Filesystem();

        return (($cwd !== null) && ($cwd !== ''))
            ? Paths::withRoot($cwd, $config, $extra, $filesystem)
            : new Paths($config, $extra, $filesystem);
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
        ?string $cwd = null
    ): Composer\Config {

        $path = getenv('PACKAGE_PATH');
        assert(is_string($path));

        return Composer\Factory::createConfig(
            $this->factoryComposerIo($input, $verbosity),
            $cwd ?? $path
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
    protected function factorySystemProcess(?string $cwd = null): SystemProcess
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
    protected function factoryPhpProcess(?string $cwd = null): PhpProcess
    {
        $php = (new PhpExecutableFinder())->find() ?: 'php';

        return new PhpProcess($php, $this->factorySystemProcess($cwd));
    }

    /**
     * @return UrlDownloader
     */
    protected function factoryUrlDownloader(): UrlDownloader
    {
        return UrlDownloader::new(
            Factory::createHttpDownloader(
                $this->factoryComposerIo(),
                $this->factoryComposerConfig()
            ),
            new Filesystem(new ComposerFilesystem())
        );
    }
}
