<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

use Composer\Util\Platform;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Io\Question;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Step that "publish" content dev folders in WP Content.
 *
 * Often a WP Starter project is made of a `composer.json` and little less, because WordPress
 * "content" packages: plugins, themes, and mu-plugins are pulled from *separate* Composer packages.
 * However, it happens that project developer want to place project-specific "content" packages in
 * the same repository of the project, because it does not worth to have a separate package for them
 * or because being very project specific there's no place to reuse them and consequently no reason
 * to maintain them separately.
 *
 * One way to do this is to just place those project-specific plugins or themes in the project
 * wp-content folder, which is the folder that will make them recognizable by WordPress, but also is
 * the same folder where Composer will place plugins and themes pulled via separate packages. This
 * introduces complexity in managing VCS, because, very likely the developer doesn't want to keep
 * Composer dependencies under version control, but surely wants to keep under version control
 * plugins and themes belonging in the project.
 *
 * WP Starter offers a different, totally optional, approach for this issue. Plugins and themes that
 * are developed in the project repository, can be placed in a dedicated folder and WP Starter will
 * either symlink or copy them to project WP content folder so that WordPress can find them.
 */
final class ContentDevStep implements OptionalStep
{
    public const NAME = 'publish-content-dev';
    public const OP_COPY = 'copy';
    public const OP_SYMLINK = 'symlink';
    public const OP_NONE = 'none';
    public const OPERATIONS = [self::OP_COPY, self::OP_SYMLINK, self::OP_NONE];

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var \Composer\Util\Filesystem
     */
    private $composerFilesystem;

    /**
     * @var string|null
     */
    private $operation;

    /**
     * @var string
     */
    private $error = 'Some errors occurred while publishing content-dev dir.';

    /**
     * @var string
     */
    private $contentDevDir = '';

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->filesystem = $locator->filesystem();
        $this->composerFilesystem = $locator->composerFilesystem();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        return $config[Config::CONTENT_DEV_DIR]->notEmpty();
    }

    /**
     * @param Config $config
     * @param Io $io
     * @return bool
     */
    public function askConfirm(Config $config, Io $io): bool
    {
        if ($config[Config::CONTENT_DEV_OPERATION]->not(self::ASK)) {
            return true;
        }

        $question = new Question(
            [
                'Which operation do you want to perform for content development folders '
                . 'to make them available in WP content dir?',
            ],
            ['s' => '[S]ymlink', 'c' => '[C]opy', 'n' => '[N]othing'],
            's'
        );

        $operation = $io->ask($question);

        if ($operation === 'n') {
            return false;
        }

        $operation === 'c' and $this->operation = self::OP_COPY;
        $operation === 's' and $this->operation = self::OP_SYMLINK;

        return true;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $operation = $this->operation;
        if (!$operation) {
            $operation = $config[Config::CONTENT_DEV_OPERATION]->unwrapOrFallback(self::OP_SYMLINK);
        }

        if ($operation === self::OP_NONE || $operation === self::ASK) {
            return Step::NONE;
        }

        /** @var string $srcBase */
        $srcBase = $config[Config::CONTENT_DEV_DIR]->unwrap();
        $this->contentDevDir = $srcBase;
        $targetBase = $paths->wpContent();

        $scrDirs = [
            "{$srcBase}/plugins",
            "{$srcBase}/themes",
            "{$srcBase}/mu-plugins",
            "{$srcBase}/languages",
        ];

        $errorsOnDirs = $operation === self::OP_COPY
            ? $this->copyDirs($scrDirs, $targetBase)
            : $this->symlinkDirs($scrDirs, $targetBase);

        $scrFiles = array_map(
            static function (string $dropin) use ($srcBase): string {
                return "{$srcBase}/{$dropin}";
            },
            DropinsStep::DROPINS
        );

        $errorsOnFiles = $operation === self::OP_COPY
            ? $this->copyFiles($scrFiles, $targetBase)
            : $this->symlinkFiles($scrFiles, $targetBase);

        $errors = $errorsOnDirs + $errorsOnFiles;
        if ($errors) {
            $this->error = sprintf(
                "%s occurred while %sing content-dev dir '%s' to '%s'.",
                $errors > 1 ? "{$errors} errors" : 'One error',
                (string)$operation,
                $srcBase,
                $targetBase
            );
            if ($operation === self::OP_SYMLINK && Platform::isWindows()) {
                $this->error .= "\nOn Windows make sure to run terminal as administrator.";
            }
        }

        if (!$errors) {
            return Step::SUCCESS;
        }

        if ($errors >= (count($scrDirs) + count($scrFiles))) {
            return Step::ERROR;
        }

        return Step::SUCCESS | Step::ERROR;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function success(): string
    {
        $message = '<comment>Development content</comment> published successfully';
        $message .= $this->contentDevDir ? " from '/{$this->contentDevDir }'." : '.';

        return $message;
    }

    /**
     * @return string
     */
    public function skipped(): string
    {
        return '  - Development content publishing skipped.';
    }

    /**
     * @param array<string> $devContentSubfolders
     * @param string $contentDir
     * @return int
     */
    private function copyDirs(array $devContentSubfolders, string $contentDir): int
    {
        $done = 0;
        $all = 0;

        foreach ($devContentSubfolders as $devContentSubfolder) {
            if (!is_dir($devContentSubfolder)) {
                continue;
            }

            $all++;

            $devContentSubfolderBase = basename($devContentSubfolder);
            $target = "{$contentDir}/{$devContentSubfolderBase}";
            $this->maybeUnlinkTarget($devContentSubfolder, $target);
            $this->filesystem->copyDir($devContentSubfolder, $target) and $done++;
        }

        return $all - $done;
    }

    /**
     * @param array<string> $devContentSubfolders
     * @param string $contentDir
     * @return int
     */
    private function symlinkDirs(array $devContentSubfolders, string $contentDir): int
    {
        $done = 0;
        $all = 0;

        foreach ($devContentSubfolders as $devContentSubfolder) {
            $items = is_dir($devContentSubfolder)
                ? glob("{$devContentSubfolder}/*", GLOB_NOSORT)
                : null;
            if (!$items) {
                continue;
            }

            $devContentSubfolderBase = basename($devContentSubfolder);
            foreach ($items as $item) {
                $all++;
                $linkPath = "{$contentDir}/{$devContentSubfolderBase}/" . basename($item);
                $this->maybeUnlinkTarget($item, $linkPath);
                $this->filesystem->removeRealDir($linkPath);
                $this->filesystem->createDir(dirname($linkPath));
                $this->filesystem->symlink($item, $linkPath) and $done++;
            }
        }

        return $all - $done;
    }

    /**
     * @param array<string> $srcFiles
     * @param string $contentDir
     * @return int
     */
    private function copyFiles(array $srcFiles, string $contentDir): int
    {
        $done = 0;
        $all = 0;

        foreach ($srcFiles as $srcFile) {
            if (!is_file($srcFile)) {
                continue;
            }

            $all++;
            $target = "{$contentDir}/" . basename($srcFile);
            $this->maybeUnlinkTarget($srcFile, $target);
            $this->filesystem->copyFile($srcFile, $target) and $done++;
        }

        return $all - $done;
    }

    /**
     * @param array<string> $srcFiles
     * @param string $contentDir
     * @return int
     */
    private function symlinkFiles(array $srcFiles, string $contentDir): int
    {
        $done = 0;
        $all = 0;

        foreach ($srcFiles as $srcFile) {
            if (!is_file($srcFile)) {
                continue;
            }

            $all++;
            $linkPath = "{$contentDir}/" . basename($srcFile);
            $this->maybeUnlinkTarget($srcFile, $linkPath);
            file_exists($linkPath) and @unlink($linkPath);
            $this->filesystem->symlink($srcFile, $linkPath) and $done++;
        }

        return $all - $done;
    }

    /**
     * @param string $source
     * @param string $target
     * @return void
     */
    private function maybeUnlinkTarget(string $source, string $target)
    {
        if (is_file($source) && is_link($target)) {
            $this->composerFilesystem->unlink($target);

            return;
        }

        if (
            $this->composerFilesystem->isSymlinkedDirectory($target)
            || $this->composerFilesystem->isJunction($target)
        ) {
            $this->composerFilesystem->removeDirectory($target);
        }
    }
}
