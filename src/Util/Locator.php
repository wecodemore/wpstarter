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
use Composer\Util\HttpDownloader;
use Composer\Util\RemoteFilesystem;
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
     */
    public function __construct(
        Requirements $requirements,
        Composer $composer,
        ComposerIo $io
    ) {

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
            ComposerFilesystem::class => $requirements->filesystem(),
        ];
    }

    /**
     * @return Config
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function config(): Config
    {
        return $this->objects[Config::class];
    }

    /**
     * @return Paths
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function paths(): Paths
    {
        return $this->objects[Paths::class];
    }

    /**
     * @return Io
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function io(): Io
    {
        return $this->objects[Io::class];
    }

    /**
     * @return ComposerIo
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function composerIo(): ComposerIo
    {
        return $this->objects[ComposerIo::class];
    }

    /**
     * @return ComposerFilesystem
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function composerFilesystem(): ComposerFilesystem
    {
        return $this->objects[ComposerFilesystem::class];
    }

    /**
     * @return ComposerConfig
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function composerConfig(): ComposerConfig
    {
        if (empty($this->objects[__FUNCTION__])) {
            /** @var Composer $composer */
            $composer = $this->objects[Composer::class];
            $this->objects[__FUNCTION__] = $composer->getConfig();
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Filesystem
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function filesystem(): Filesystem
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Filesystem($this->composerFilesystem());
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return UrlDownloader
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function urlDownloader(): UrlDownloader
    {
        if (empty($this->objects[__FUNCTION__])) {
            $factory = null;
            if (method_exists(\Composer\Factory::class, 'createHttpDownloader')) {
                $factory = [\Composer\Factory::class, 'createHttpDownloader'];
            } elseif (method_exists(\Composer\Factory::class, 'createRemoteFilesystem')) {
                $factory = [\Composer\Factory::class, 'createRemoteFilesystem'];
            }

            if (!$factory) {
                throw new \Exception('Could not instantiate HttpDownloader');
            }

            $composerIo = $this->composerIo();

            /**
             * @var callable $factory
             * @var HttpDownloader|RemoteFilesystem $client
             */
            $client = $factory($composerIo, $this->composerConfig());

            $filesystem = $this->filesystem();
            $verbose = $composerIo->isVerbose();

            $this->objects[__FUNCTION__] = ($client instanceof HttpDownloader)
                ? UrlDownloader::newV2($client, $filesystem, $verbose)
                : UrlDownloader::newV1($client, $filesystem, $verbose);
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return FileContentBuilder
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function fileContentBuilder(): FileContentBuilder
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new FileContentBuilder();
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return OverwriteHelper
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function overwriteHelper(): OverwriteHelper
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new OverwriteHelper(
                $this->config(),
                $this->io(),
                $this->paths()->root(),
                $this->composerFilesystem()
            );
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Salter
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function salter(): Salter
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Salter();
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Cli\PharInstaller
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function pharInstaller(): Cli\PharInstaller
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Cli\PharInstaller(
                $this->io(),
                $this->urlDownloader()
            );
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return PackageFinder
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function packageFinder(): PackageFinder
    {
        if (empty($this->objects[__FUNCTION__])) {
            /** @var Composer $composer */
            $composer = $this->objects[Composer::class];
            $this->objects[__FUNCTION__] = new PackageFinder(
                $composer->getRepositoryManager()->getLocalRepository(),
                $composer->getInstallationManager(),
                $this->composerFilesystem()
            );
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return WpConfigSectionEditor
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function wpConfigSectionEditor(): WpConfigSectionEditor
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new WpConfigSectionEditor(
                $this->paths(),
                $this->composerFilesystem()
            );
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return MuPluginList
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function muPluginsList(): MuPluginList
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new MuPluginList(
                $this->packageFinder(),
                $this->paths(),
                $this->composerFilesystem()
            );
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return WordPressEnvBridge
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function env(): WordPressEnvBridge
    {
        if (empty($this->objects[__FUNCTION__])) {
            /** @var string $file */
            $file = $this->config()[Config::ENV_FILE]->unwrapOrFallback('.env');
            /** @var string $dir */
            $dir = $this->config()[Config::ENV_DIR]->unwrapOrFallback($this->paths()->root());
            $bridge = new WordPressEnvBridge();
            $bridge->load($file, $dir);
            $environment = $bridge->determineEnvType();
            ($environment !== 'example') and $bridge->load("{$file}.{$environment}", $dir);
            $this->objects[__FUNCTION__] = $bridge;
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Cli\SystemProcess
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function systemProcess(): Cli\SystemProcess
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Cli\SystemProcess($this->paths(), $this->io());
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return ExecutableFinder
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function executableFinder(): ExecutableFinder
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new ExecutableFinder();
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Cli\PhpProcess
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function phpProcess(): Cli\PhpProcess
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Cli\PhpProcess(
                $this->php,
                $this->systemProcess()
            );
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Cli\PhpToolProcessFactory
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function phpToolProcessFactory(): Cli\PhpToolProcessFactory
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new Cli\PhpToolProcessFactory(
                $this->paths(),
                $this->io(),
                new Cli\PharInstaller($this->io(), $this->urlDownloader()),
                $this->packageFinder(),
                $this->phpProcess()
            );
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return Cli\PhpToolProcess
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function wpCliProcess(): Cli\PhpToolProcess
    {
        if (empty($this->objects[__FUNCTION__])) {
            $tool = new Cli\WpCliTool($this->config(), $this->urlDownloader(), $this->io());
            $factory = $this->phpToolProcessFactory();
            $this->objects[__FUNCTION__] = $factory->create($tool, $this->php);
        }

        return $this->objects[__FUNCTION__];
    }

    /**
     * @return DbChecker
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function dbChecker(): DbChecker
    {
        if (empty($this->objects[__FUNCTION__])) {
            $this->objects[__FUNCTION__] = new DbChecker(
                $this->env(),
                $this->io(),
                $this->systemProcess(),
                $this->executableFinder()
            );
        }

        return $this->objects[__FUNCTION__];
    }
}
