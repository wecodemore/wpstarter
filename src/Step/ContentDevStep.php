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
     * @inheritdoc
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
        return
            $config[Config::CONTENT_DEV_OPERATION]->notEmpty()
            && $config[Config::CONTENT_DEV_DIR]->notEmpty();
    }

    /**
     * @param Config $config
     * @param Io $io
     * @return bool
     */
    public function askConfirm(Config $config, Io $io): bool
    {
        $dir = $config[Config::CONTENT_DEV_DIR]->unwrapOrFallback();
        if (!$dir || $config[Config::CONTENT_DEV_OPERATION]->not(self::ASK)) {
            return true;
        }

        $operation = $this->io->ask(
            [
                'Which operation do you want to perform',
                "for content development folders in /{$dir}",
                'to make them available in WP content dir?',
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
        $operation = $this->operation ?: $config[Config::CONTENT_DEV_OPERATION]->unwrap();

        if ($operation === self::OP_NONE || $operation === self::ASK) {
            return Step::NONE;
        }

        $srcBase = $config[Config::CONTENT_DEV_DIR]->unwrap();
        $sourceSubDirs = glob("{$srcBase}/*", GLOB_ONLYDIR | GLOB_NOSORT);
        $targetBase = $paths->wpContent();

        $operationDirs = $operation === self::OP_COPY
            ? $this->copyDirs($srcBase, $sourceSubDirs, $targetBase)
            : $this->symlinkDirs($srcBase, $sourceSubDirs, $targetBase);

        $operationFiles = $this->copyOrSymlinkFiles($srcBase, $targetBase, $operation);

        $this->error = sprintf(
            "Some errors occurred while %sing content-dev dir '%s' to '%s'.",
            $operation,
            $srcBase,
            $targetBase
        );

        return ($operationDirs && $operationFiles) ? Step::SUCCESS : Step::ERROR;
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
        return '  - Content-dev publishing skipped.';
    }

    /**
     * @param string $srcBase
     * @param array $srcSubDirs
     * @param string $targetBase
     * @return bool
     */
    private function copyDirs(string $srcBase, array $srcSubDirs, string $targetBase): bool
    {
        $done = $all = 0;

        foreach ($srcSubDirs as $sourceSubDir) {
            if (!is_dir("{$srcBase}/{$sourceSubDir}")) {
                continue;
            }

            $all++;
            $targetFullPath = "{$targetBase}/{$sourceSubDir}";

            $this->filesystem->copyDir("{$srcBase}/{$sourceSubDir}", $targetFullPath) and $done++;
        }

        return $done === $all;
    }

    /**
     * @param string $srcBase
     * @param array $srcSubDirs
     * @param string $targetBase
     * @return bool
     */
    private function symlinkDirs(string $srcBase, array $srcSubDirs, string $targetBase): bool
    {
        $done = $all = 0;

        foreach ($srcSubDirs as $sourceSubDir) {
            $srcInnerPaths = glob("{$srcBase}/{$sourceSubDir}/*", GLOB_NOSORT);
            $this->filesystem->createDir("{$targetBase}/{$sourceSubDir}/");
            foreach ($srcInnerPaths as $srcInnerPath) {
                $all++;
                $targetName = "{$targetBase}/{$sourceSubDir}/" . basename($srcInnerPath);
                $this->filesystem->symlink($srcInnerPath, $targetName) and $done++;
            }
        }

        return $done === $all;
    }

    /**
     * @param string $sourceBase
     * @param string $targetBase
     * @param string $operation
     * @return bool
     */
    private function copyOrSymlinkFiles(
        string $sourceBase,
        string $targetBase,
        string $operation
    ): bool {

        $done = $all = 0;

        $files = glob("{$sourceBase}/*.*", GLOB_NOSORT);
        foreach ($files as $file) {
            $all++;
            $success = $operation === self::OP_COPY
                ? $this->filesystem->copyFile($file, "{$targetBase}/" . basename($file))
                : $this->filesystem->symlink($file, "{$targetBase}/" . basename($file));

            $success and $done++;
        }

        return $done === $all;
    }
}
