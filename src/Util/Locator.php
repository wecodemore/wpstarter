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
use WeCodeMore\WpStarter\WpCli\PharInstaller;
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
     * @return FileBuilder
     */
    public function fileBuilder(): FileBuilder
    {
        if (empty($this->objects[FileBuilder::class])) {
            $this->objects[FileBuilder::class] = new FileBuilder();
        }

        return $this->objects[FileBuilder::class];
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
     * @return PharInstaller
     */
    public function pharInstaller(): PharInstaller
    {
        if (empty($this->objects[PharInstaller::class])) {
            $this->objects[PharInstaller::class] = new PharInstaller(
                $this->io(),
                $this->urlDownloader()
            );
        }

        return $this->objects[LanguageListFetcher::class];
    }

    /**
     * @return MuPluginList
     */
    public function muPluginsList(): MuPluginList
    {
        if (empty($this->objects[MuPluginList::class])) {
            $composer = $this->objects[Composer::class];
            $this->objects[MuPluginList::class] = new MuPluginList(
                $composer->getRepositoryManager()->getLocalRepository(),
                $composer->getInstallationManager()
            );
        }

        return $this->objects[LanguageListFetcher::class];
    }
}
