<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface as ComposerIo;
use Composer\Util\Filesystem as ComposerFilesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use WeCodeMore\WpStarter\Env\WordPressEnvBridge;
use WeCodeMore\WpStarter\Cli;
use WeCodeMore\WpStarter\Config\Config;

/**
 * Service locator for WP Starter objects that is passed to Steps so the can do what they need.
 */
final class Locator
{
    /**
     * @var array
     */
    private $objects;

    /**
     * @var string
     */
    private $php;

    /**
     * @param Requirements $requirements
     * @param Composer $composer
     * @param ComposerIo $io
     * @param ComposerFilesystem $filesystem
     */
    public function __construct(
        Requirements $requirements,
        Composer $composer,
        ComposerIo $io,
        ComposerFilesystem $filesystem
    ) {

        if (!$this->objects) {
            $php = (new PhpExecutableFinder())->find();
            if (!$php) {
                throw new \Exception('PHP executable not found.');
            }

            $this->php = $php;
            $this->objects = [
                Config::class => $requirements->config(),
                Paths::class => $requirements->paths(),
                Io::class => $requirements->io(),
                ComposerIo::class => $io,
                Composer::class => $composer,
                ComposerFilesystem::class => $filesystem,
            ];
        }
    }

    /**
     * @return Config
     */
    public function config(): Config
    {
        return $this->objects[Config::class];
    }

    /**
     * @return Paths
     */
    public function paths(): Paths
    {
        return $this->objects[Paths::class];
    }

    /**
     * @return Io
     */
    public function io(): Io
    {
        return $this->objects[Io::class];
    }

    /**
     * @return ComposerFilesystem
     */
    public function composerFilesystem(): ComposerFilesystem
    {
        return $this->objects[ComposerFilesystem::class];
    }

    /**
     * @return Filesystem
     */
    public function filesystem(): Filesystem
    {
        if (empty($this->objects[Filesystem::class])) {
            $this->objects[Filesystem::class] = new Filesystem(
                $this->objects[ComposerFilesystem::class]
            );
        }

        return $this->objects[Filesystem::class];
    }

    /**
     * @return UrlDownloader
     */
    public function urlDownloader(): UrlDownloader
    {
        if (empty($this->objects[UrlDownloader::class])) {
            $this->objects[UrlDownloader::class] = new UrlDownloader(
                $this->objects[ComposerFilesystem::class],
                Factory::createRemoteFilesystem(
                    $this->objects[ComposerIo::class],
                    $this->objects[Composer::class]->getConfig()
                )
            );
        }

        return $this->objects[UrlDownloader::class];
    }

    /**
     * @return FileContentBuilder
     */
    public function fileContentBuilder(): FileContentBuilder
    {
        if (empty($this->objects[FileContentBuilder::class])) {
            $this->objects[FileContentBuilder::class] = new FileContentBuilder();
        }

        return $this->objects[FileContentBuilder::class];
    }

    /**
     * @return OverwriteHelper
     */
    public function overwriteHelper(): OverwriteHelper
    {
        if (empty($this->objects[OverwriteHelper::class])) {
            $this->objects[OverwriteHelper::class] = new OverwriteHelper(
                $this->config(),
                $this->io(),
                $this->paths()->root(),
                $this->objects[ComposerFilesystem::class]
            );
        }

        return $this->objects[OverwriteHelper::class];
    }

    /**
     * @return Salter
     */
    public function salter(): Salter
    {
        if (empty($this->objects[Salter::class])) {
            $this->objects[Salter::class] = new Salter();
        }

        return $this->objects[Salter::class];
    }

    /**
     * @return LanguageListFetcher
     */
    public function languageListFetcher(): LanguageListFetcher
    {
        if (empty($this->objects[LanguageListFetcher::class])) {
            $this->objects[LanguageListFetcher::class] = new LanguageListFetcher(
                $this->io(),
                $this->urlDownloader()
            );
        }

        return $this->objects[LanguageListFetcher::class];
    }

    /**
     * @return Cli\PharInstaller
     */
    public function pharInstaller(): Cli\PharInstaller
    {
        if (empty($this->objects[Cli\PharInstaller::class])) {
            $this->objects[Cli\PharInstaller::class] = new Cli\PharInstaller(
                $this->io(),
                $this->urlDownloader()
            );
        }

        return $this->objects[Cli\PharInstaller::class];
    }

    /**
     * @return PackageFinder
     */
    public function packageFinder(): PackageFinder
    {
        if (empty($this->objects[PackageFinder::class])) {
            /** @var Composer $composer */
            $composer = $this->objects[Composer::class];
            $this->objects[PackageFinder::class] = new PackageFinder(
                $composer->getRepositoryManager()->getLocalRepository(),
                $composer->getInstallationManager(),
                $this->composerFilesystem()
            );
        }

        return $this->objects[PackageFinder::class];
    }

    /**
     * @return MuPluginList
     */
    public function muPluginsList(): MuPluginList
    {
        if (empty($this->objects[MuPluginList::class])) {
            $this->objects[MuPluginList::class] = new MuPluginList(
                $this->packageFinder(),
                $this->paths()
            );
        }

        return $this->objects[MuPluginList::class];
    }

    /**
     * @return WordPressEnvBridge
     */
    public function wordPressEnvBridge(): WordPressEnvBridge
    {
        if (empty($this->objects[WordPressEnvBridge::class])) {
            $file = $this->config()[Config::ENV_FILE]->unwrapOrFallback('.env');
            $dir = $this->config()[Config::ENV_DIR]->unwrapOrFallback($this->paths()->root());
            $this->objects[WordPressEnvBridge::class] = WordPressEnvBridge::load($dir, $file);
        }

        return $this->objects[WordPressEnvBridge::class];
    }

    /**
     * @return Cli\SystemProcess
     */
    public function systemProcess(): Cli\SystemProcess
    {
        if (empty($this->objects[Cli\SystemProcess::class])) {
            $this->objects[Cli\SystemProcess::class] = new Cli\SystemProcess(
                $this->paths(),
                $this->io()
            );
        }

        return $this->objects[Cli\SystemProcess::class];
    }

    /**
     * @return Cli\PhpProcess
     */
    public function phpProcess(): Cli\PhpProcess
    {
        if (empty($this->objects[Cli\PhpProcess::class])) {
            $this->objects[Cli\PhpProcess::class] = new Cli\PhpProcess(
                $this->php,
                $this->paths(),
                $this->io()
            );
        }

        return $this->objects[Cli\PhpProcess::class];
    }

    /**
     * @return Cli\PhpToolProcessFactory
     */
    public function phpToolProcessFactory(): Cli\PhpToolProcessFactory
    {
        if (!empty($this->objects[Cli\phpToolProcessFactory::class])) {
            return $this->objects[Cli\phpToolProcessFactory::class];
        }

        return new Cli\PhpToolProcessFactory(
            $this->config(),
            $this->paths(),
            $this->io(),
            new Cli\PharInstaller($this->io(), $this->urlDownloader()),
            $this->packageFinder()
        );
    }

    /**
     * @return Cli\WpCliTool
     */
    public function wpCliTool(): Cli\WpCliTool
    {
        if (empty($this->objects[Cli\WpCliTool::class])) {
            $this->objects[Cli\WpCliTool::class] = new Cli\WpCliTool(
                $this->config(),
                $this->urlDownloader()
            );
        }

        return $this->objects[Cli\WpCliTool::class];
    }

    /**
     * @return \ArrayObject
     */
    public function wpCliEnvironment(): \ArrayObject
    {
        if (empty($this->objects[__METHOD__])) {
            $env = $this->wpCliTool()->processEnvVars($this->paths(), $this->wordPressEnvBridge());
            $this->objects[__METHOD__] = new \ArrayObject($env);
        }

        return $this->objects[__METHOD__];
    }

    /**
     * @return Cli\PhpToolProcess
     */
    public function wpCliProcess(): Cli\PhpToolProcess
    {
        if (!empty($this->objects[__METHOD__])) {
            return $this->objects[__METHOD__];
        }

        $this->objects[__METHOD__] = $this
            ->phpToolProcessFactory()
            ->create($this->wpCliTool(), $this->php)
            ->withEnvironment($this->wpCliEnvironment()->getArrayCopy());

        return $this->objects[Cli\PhpToolProcess::class];
    }
}
