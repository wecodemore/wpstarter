<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Io;
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
    const NAME = 'publish-content-dev';

    const OP_COPY = 'copy';
    const OP_SYMLINK = 'symlink';
    const OP_NONE = 'none';
    const OPERATIONS = [self::OP_COPY, self::OP_SYMLINK, self::OP_NONE];

    /**
     * @var \WeCodeMore\WpStarter\Util\Io
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var \WeCodeMore\WpStarter\Config\Config
     */
    private $config;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var string
     */
    private $error = 'Some errors occurred while publishing content-dev dir.';

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->io = $locator->io();
        $this->filesystem = $locator->filesystem();
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

        $operation = $this->io->ask(
            [
                'Which operation do you want to perform for content development folders to make '
                .'them available in WP content dir?',
            ],
            ['s' => '[S]ymlink', 'c' => '[C]opy', 'n' => '[N]othing'],
            'n'
        );

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
        $this->config = $config;
        $operation = $this->operation;
        if (!$operation) {
            $operation = $config[Config::CONTENT_DEV_OPERATION]->unwrapOrFallback(self::OP_SYMLINK);
        }

        if ($operation === self::OP_NONE || $operation === self::ASK) {
            return Step::NONE;
        }

        $srcBase = $config[Config::CONTENT_DEV_DIR]->unwrap();
        $scrDirs = ["{$srcBase}/plugins", "{$srcBase}/themes", "{$srcBase}/mu-plugins"];
        $targetBase = $paths->wpContent();

        $successDirs = $operation === self::OP_COPY
            ? $this->copyDirs($scrDirs, $targetBase)
            : $this->symlinkDirs($scrDirs, $targetBase);

        $scrFiles = array_map(
            function (string $dropin) use ($srcBase): string {
                return "{$srcBase}/{$dropin}";
            },
            DropinsStep::DROPINS
        );

        $successFiles = $operation === self::OP_COPY
            ? $this->copyFiles($scrFiles, $targetBase)
            : $this->symlinkFiles($scrFiles, $targetBase);

        $this->error = sprintf(
            "Some errors occurred while %sing content-dev dir '%s' to '%s'.",
            $operation,
            $srcBase,
            $targetBase
        );

        if ($successDirs && $successFiles) {
            return Step::SUCCESS;
        }

        if (!$successDirs && !$successFiles) {
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
        $dir = $this->config[Config::CONTENT_DEV_DIR]->unwrap();

        return "<comment>Development content</comment> published successfully from '/{$dir}'.";
    }

    /**
     * @return string
     */
    public function skipped(): string
    {
        return '  - Development content publishing skipped.';
    }

    /**
     * @param array $srcDirs
     * @param string $targetDir
     * @return bool
     */
    private function copyDirs(array $srcDirs, string $targetDir): bool
    {
        $done = $all = 0;

        foreach ($srcDirs as $srcSubDir) {
            if (!is_dir($srcSubDir)) {
                continue;
            }

            $all++;
            $targetFullPath = "{$targetDir}/" . basename($srcSubDir);

            $this->filesystem->copyDir($srcSubDir, $targetFullPath) and $done++;
        }

        return $done === $all;
    }

    /**
     * @param array $srcDirs
     * @param string $targetDir
     * @return bool
     */
    private function symlinkDirs(array $srcDirs, string $targetDir): bool
    {
        $done = $all = 0;

        foreach ($srcDirs as $srcDir) {
            $srcSubDirs = glob("{$srcDir}/*", GLOB_NOSORT);
            $this->filesystem->createDir($srcDir);
            foreach ($srcSubDirs as $srcSubDir) {
                $all++;
                $targetName = "{$targetDir}/" . basename($srcSubDir);
                $this->filesystem->symlink($srcSubDir, $targetName) and $done++;
            }
        }

        return $done === $all;
    }

    /**
     * @param array $srcFiles
     * @param string $targetDir
     * @return bool
     */
    private function copyFiles(array $srcFiles, string $targetDir): bool
    {
        $done = $all = 0;

        foreach ($srcFiles as $srcFile) {
            if (!is_file($srcFile)) {
                continue;
            }

            $all++;
            $this->filesystem->copyFile($srcFile, "{$targetDir}/" . basename($srcFile)) and $done++;
        }

        return $done === $all;
    }

    /**
     * @param array $srcFiles
     * @param string $targetDir
     * @return bool
     */
    private function symlinkFiles(array $srcFiles, string $targetDir): bool
    {
        $done = $all = 0;

        foreach ($srcFiles as $srcFile) {
            if (!is_file($srcFile)) {
                continue;
            }

            $all++;
            $this->filesystem->symlink($srcFile, "{$targetDir}/" . basename($srcFile)) and $done++;
        }

        return $done === $all;
    }
}
