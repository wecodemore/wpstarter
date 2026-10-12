<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use Composer\Composer;
use Composer\IO\IOInterface as ComposerIo;
use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Config as ComposerConfig;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use WeCodeMore\WpStarter\Env\WordPressEnvBridge;
use WeCodeMore\WpStarter\Cli;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Io\Io;

/**
 * Service locator for WP Starter objects that is passed to Steps for their convenience.
 */
final class Locator
{
    /** @var array<string, object>  */
    private array $objects;
    private string $php;

    /**
     * @param Requirements $requirements
     * @param Composer $composer
     * @param ComposerIo $io
     */
    public function __construct(
        Requirements $requirements,
        Composer $composer,
        ComposerIo $io
    ) {

        $php = (new PhpExecutableFinder())->find();
        if (($php === false) || ($php === '')) {
            throw new \Exception('PHP executable not found.');
        }

        $this->php = $php;

        $this->objects = [
            Config::class => $requirements->config(),
            Paths::class => $requirements->paths(),
            Io::class => $requirements->io(),
            ComposerIo::class => $io,
            Composer::class => $composer,
            ComposerFilesystem::class => $requirements->filesystem(),
        ];
    }

    /**
     * @return Config
     */
    public function config(): Config
    {
        /** @var Config */
        return $this->objects[Config::class];
    }

    /**
     * @return Paths
     */
    public function paths(): Paths
    {
        /** @var Paths */
        return $this->objects[Paths::class];
    }

    /**
     * @return Io
     */
    public function io(): Io
    {
        /** @var Io */
        return $this->objects[Io::class];
    }

    /**
     * @return ComposerIo
     */
    public function composerIo(): ComposerIo
    {
        /** @var ComposerIo */
        return $this->objects[ComposerIo::class];
    }

    /**
     * @return ComposerFilesystem
     */
    public function composerFilesystem(): ComposerFilesystem
    {
        /** @var ComposerFilesystem */
        return $this->objects[ComposerFilesystem::class];
    }

    /**
     * @return ComposerConfig
     */
    public function composerConfig(): ComposerConfig
    {
        if (!isset($this->objects[__FUNCTION__])) {
            /** @var Composer $composer */
            $composer = $this->objects[Composer::class];
            $this->objects[__FUNCTION__] = $composer->getConfig();
        }

        /** @var ComposerConfig */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Filesystem
     */
    public function filesystem(): Filesystem
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Filesystem($this->composerFilesystem());
        }

        /** @var Filesystem */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return UrlDownloader
     */
    public function urlDownloader(): UrlDownloader
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = UrlDownloader::new(
                \Composer\Factory::createHttpDownloader(
                    $this->composerIo(),
                    $this->composerConfig()
                ),
                $this->filesystem(),
            );
        }

        /** @var UrlDownloader */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return FileContentBuilder
     */
    public function fileContentBuilder(): FileContentBuilder
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new FileContentBuilder();
        }

        /** @var FileContentBuilder */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return OverwriteHelper
     */
    public function overwriteHelper(): OverwriteHelper
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new OverwriteHelper(
                $this->config(),
                $this->io(),
                $this->paths()->root(),
                $this->composerFilesystem()
            );
        }

        /** @var OverwriteHelper */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Salter
     */
    public function salter(): Salter
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Salter();
        }

        /** @var Salter */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Cli\PharInstaller
     */
    public function pharInstaller(): Cli\PharInstaller
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Cli\PharInstaller(
                $this->io(),
                $this->urlDownloader()
            );
        }

        /** @var Cli\PharInstaller */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return PackageFinder
     */
    public function packageFinder(): PackageFinder
    {
        if (!isset($this->objects[__FUNCTION__])) {
            /** @var Composer $composer */
            $composer = $this->objects[Composer::class];
            $this->objects[__FUNCTION__] = new PackageFinder(
                $composer->getRepositoryManager()->getLocalRepository(),
                $composer->getInstallationManager(),
                $this->composerFilesystem()
            );
        }

        /** @var PackageFinder */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return WpConfigSectionEditor
     */
    public function wpConfigSectionEditor(): WpConfigSectionEditor
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new WpConfigSectionEditor(
                $this->paths(),
                $this->composerFilesystem()
            );
        }

        /** @var WpConfigSectionEditor */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return MuPluginList
     */
    public function muPluginsList(): MuPluginList
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new MuPluginList(
                $this->packageFinder(),
                $this->paths(),
                $this->composerFilesystem()
            );
        }

        /** @var MuPluginList */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return WordPressEnvBridge
     */
    public function env(): WordPressEnvBridge
    {
        if (!isset($this->objects[__FUNCTION__])) {
            /** @var string $file */
            $file = $this->config()[Config::ENV_FILE]->unwrapOrFallback('.env');
            /** @var string $dir */
            $dir = $this->config()[Config::ENV_DIR]->unwrapOrFallback($this->paths()->root());
            $bridge = new WordPressEnvBridge();
            $bridge->load($file, $dir);
            $environment = $bridge->determineEnvType();
            ($environment !== 'example') and $bridge->loadAppended("{$file}.{$environment}", $dir);
            $this->objects[__FUNCTION__] = $bridge;
        }

        /** @var WordPressEnvBridge */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Cli\SystemProcess
     */
    public function systemProcess(): Cli\SystemProcess
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Cli\SystemProcess($this->paths(), $this->io());
        }

        /** @var Cli\SystemProcess */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return ExecutableFinder
     */
    public function executableFinder(): ExecutableFinder
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new ExecutableFinder();
        }

        /** @var ExecutableFinder */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Cli\PhpProcess
     */
    public function phpProcess(): Cli\PhpProcess
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Cli\PhpProcess(
                $this->php,
                $this->systemProcess()
            );
        }

        /** @var Cli\PhpProcess */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Cli\PhpToolProcessFactory
     */
    public function phpToolProcessFactory(): Cli\PhpToolProcessFactory
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Cli\PhpToolProcessFactory(
                $this->paths(),
                $this->io(),
                new Cli\PharInstaller($this->io(), $this->urlDownloader()),
                $this->packageFinder(),
                $this->phpProcess()
            );
        }

        /** @var Cli\PhpToolProcessFactory */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Cli\PhpToolProcess
     */
    public function wpCliProcess(): Cli\PhpToolProcess
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $tool = new Cli\WpCliTool($this->config(), $this->urlDownloader(), $this->io());
            $factory = $this->phpToolProcessFactory();
            $this->objects[__FUNCTION__] = $factory->create($tool, $this->php);
        }

        /** @var Cli\PhpToolProcess */
        return $this->objects[__FUNCTION__];
    }

    /**
     * @return DbChecker
     */
    public function dbChecker(): DbChecker
    {
        if (!isset($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new DbChecker(
                $this->env(),
                $this->io(),
                $this->systemProcess(),
                $this->executableFinder()
            );
        }

        /** @var DbChecker */
        return $this->objects[__FUNCTION__];
    }
}
