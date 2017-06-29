<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Utils\Config;
use WeCodeMore\WpStarter\Utils\Filesystem;
use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\Paths;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class ContentDevStep implements OptionalStepInterface, FileStepInterface
{
    const NAME = 'publish-content-dev';

    const OP_COPY = 'copy';
    const OP_SYMLINK = 'symlink';
    const OP_NONE = 'none';
    const OPERATIONS = [self::OP_COPY, self::OP_SYMLINK, self::OP_NONE];

    /**
     * @var \WeCodeMore\WpStarter\Utils\IO
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Filesystem
     */
    private $filesystem;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Config
     */
    private $config;

    /**
     * @var string
     */
    private $operation = self::OP_NONE;

    /**
     * @var string
     */
    private $error = 'Some errors occurred while publishing content-dev dir.';

    /**
     * @param \WeCodeMore\WpStarter\Utils\IO $io
     * @param \WeCodeMore\WpStarter\Utils\Filesystem $filesystem
     */
    public function __construct(IO $io, Filesystem $filesystem)
    {
        $this->io = $io;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return self::NAME;
    }

    /**
     * Return true if the step is allowed, i.e. the run method have to be called or not
     *
     * @param \WeCodeMore\WpStarter\Utils\Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths)
    {
        if ($config[Config::CONTENT_DEV_OPERATION] && $config[Config::CONTENT_DEV_DIR]) {
            $this->config = $config;

            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function askConfirm(Config $config, IO $io)
    {
        $dir = $this->config[Config::CONTENT_DEV_DIR];
        if (!$dir || $this->config[Config::CONTENT_DEV_OPERATION] !== 'ask') {
            return true;
        }

        $answers = ['s' => '[S]imlink', 'c' => '[C]opy', 'n' => '[N]othing'];
        $operation = $this->io->ask([
            'Which operation do you want to perform',
            "for content-dev folders in /{$dir}",
            'to make them available in WP content dir?'
        ], $answers, 'n');

        is_string($operation) and $operation = strtolower($operation);

        if ($operation === 'n') {
            return false;
        }

        $operation === 'c' and $this->operation = self::OP_COPY;
        $operation === 's' and $this->operation = self::OP_SYMLINK;

        return true;
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function run(Paths $paths, $verbosity)
    {
        $operation = $this->config[Config::CONTENT_DEV_OPERATION];

        if ($operation === self::OP_NONE) {
            return self::OP_NONE;
        }

        $dir = $this->config[Config::CONTENT_DEV_DIR];

        if (!$dir) {
            $format = "Configured 'content-dev' dir %s, doesn\'t exist. Can\'t %s into it.";
            $this->error = sprintf($format, $dir, $operation);

            return self::ERROR;
        }

        $sourceBase = $paths->root($dir);

        if (!is_dir($sourceBase) || !in_array($operation, [self::OP_COPY, self::OP_SYMLINK],
                true)
        ) {
            $format = "'content-dev' operation %s, is not valid, only '%s' or '%s' are accepted.";
            $this->error = sprintf($format, $operation, self::OP_COPY, self::OP_SYMLINK);

            return self::ERROR;
        }

        $sourceSubDirs = glob("{$sourceBase}/*", GLOB_ONLYDIR | GLOB_NOSORT);
        $targetBase = $paths->wp_content();

        $operationDirs = $operation === self::OP_COPY
            ? $this->copyDirs($sourceBase, $sourceSubDirs, $targetBase)
            : $this->symlinkDirs($sourceBase, $sourceSubDirs, $targetBase);

        $operationFiles = $this->copyOrSymlinkFiles($sourceBase, $targetBase, $operation);

        $this->error = sprintf(
            "Some errors occurred while %sing content-dev dir '%s' to '%s'.",
            $operation,
            $sourceBase,
            $targetBase
        );

        return ($operationDirs && $operationFiles) ? self::SUCCESS : self::ERROR;
    }

    /**
     * @inheritdoc
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        $dir = $this->config[Config::CONTENT_DEV_DIR];

        return "<comment>Development content</comment> publishing done successfully from '/{$dir}'.";
    }

    /**
     * @inheritdoc
     */
    public function skipped()
    {
        return 'Content-dev publishing skipped.';
    }

    /**
     * @param string $sourceBase
     * @param array $sourceSubDirs
     * @param string $targetBase
     * @return bool
     */
    private function copyDirs($sourceBase, array $sourceSubDirs, $targetBase)
    {
        $done = $all = 0;

        foreach ($sourceSubDirs as $sourceSubDir) {

            $sourceFullPath = "{$sourceBase}/{$sourceSubDir}";

            if (!is_dir($sourceFullPath)) {
                continue;
            }

            $all++;
            $targetFullPath = "{$targetBase}/{$sourceSubDir}";

            $this->filesystem->copyDir($sourceFullPath, $targetFullPath) and $done++;

        }

        return $done === $all;
    }

    /**
     * @param string $sourceBase
     * @param array $sourceSubDirs
     * @param string $targetBase
     * @return bool
     */
    private function symlinkDirs($sourceBase, array $sourceSubDirs, $targetBase)
    {
        $done = $all = 0;

        foreach ($sourceSubDirs as $sourceSubDir) {
            $sourceInner = glob("{$sourceBase}/{$sourceSubDir}/*", GLOB_NOSORT);
            foreach ($sourceInner as $sourceInnerItem) {
                $all++;
                $targetName = "{$targetBase}/{$sourceSubDir}/" . basename($sourceInnerItem);
                $this->filesystem->createDir("{$targetBase}/{$sourceSubDir}/");
                $this->filesystem->symlink($sourceInnerItem, $targetName) and $done++;
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
    private function copyOrSymlinkFiles($sourceBase, $targetBase, $operation)
    {
        $done = $all = 0;

        $files = glob("{$sourceBase}/*.*", GLOB_NOSORT);
        foreach ($files as $file) {
            $all++;
            $method = $operation === self::OP_COPY ? 'copyFile' : 'symlink';
            /** @var callable $cb */
            $cb = [$this->filesystem, $method];
            $cb($file, "{$targetBase}/" . basename($file)) and $done++;
        }

        return $done === $all;
    }
}
