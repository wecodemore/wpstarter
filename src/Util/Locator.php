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
use Composer\Util\RemoteFilesystem;
use WeCodeMore\WpStarter\Config\Config;
use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Config as ComposerConfig;
use Composer\IO\IOInterface as ComposerIo;

final class Locator
{
    /**
     * @var array
     */
    private $objects;

    /**
     * @param Requirements $requirements
     * @param Composer $composer
     */
    public function __construct(Requirements $requirements, Composer $composer)
    {
        if (!$this->objects) {
            $this->objects = [
                Config::class => $requirements->config(),
                Paths::class => $requirements->paths(),
                Io::class => $requirements->io(),
                Composer::class => $composer,
                ComposerConfig::class => $requirements->composerConfig(),
                ComposerIo::class => $requirements->composerIo(),
                ComposerFilesystem::class => $requirements->composerFilesystem(),
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
     * @return Composer
     */
    public function composer(): Composer
    {
        return $this->objects[Composer::class];
    }

    /**
     * @return ComposerConfig
     */
    public function composerConfig(): ComposerConfig
    {
        return $this->objects[ComposerConfig::class];
    }

    /**
     * @return ComposerIo
     */
    public function composerIo(): ComposerIo
    {
        return $this->objects[ComposerIo::class];
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
            $this->objects[Filesystem::class] = new Filesystem($this->composerFilesystem());
        }

        return $this->objects[Filesystem::class];
    }

    /**
     * @return RemoteFilesystem
     */
    public function remoteFilesystem(): RemoteFilesystem
    {
        if (empty($this->objects[RemoteFilesystem::class])) {
            $this->objects[RemoteFilesystem::class] = Factory::createRemoteFilesystem(
                $this->composerIo(),
                $this->composerConfig()
            );
        }

        return $this->objects[RemoteFilesystem::class];
    }

    /**
     * @return UrlDownloader
     */
    public function urlDownloader(): UrlDownloader
    {
        if (empty($this->objects[UrlDownloader::class])) {
            $this->objects[UrlDownloader::class] = new UrlDownloader(
                $this->composerFilesystem(),
                $this->remoteFilesystem()
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
                $this->paths()
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
}
