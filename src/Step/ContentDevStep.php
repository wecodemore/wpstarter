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
use Symfony\Component\Finder\Finder;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Io\Question;
use WeCodeMore\WpStarter\Util\Filesystem;
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
    public const NAME = 'publishcontentdev';

    /**
     * @var Filesystem
     */
    private $filesystem;

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
            ['a' => '[A]uto', 's' => '[S]ymlink', 'c' => '[C]opy', 'n' => '[N]othing'],
            'a'
        );

        $answer = $io->ask($question);
        if (($answer === 'n') || !$answer) {
            return false;
        }

        $this->operation = [
            'a' => Filesystem::OP_AUTO,
            's' => Filesystem::OP_SYMLINK,
            'c' => Filesystem::OP_COPY,
        ][$answer];

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
            /** @var string $operation */
            $operation = $config[Config::CONTENT_DEV_OPERATION]
                ->unwrapOrFallback(Filesystem::OP_AUTO);
        }

        if (($operation === Filesystem::OP_NONE) || ($operation === self::ASK)) {
            return Step::NONE;
        }

        /** @var string $dirName */
        $dirName = $config[Config::CONTENT_DEV_DIR]->unwrap();
        $src = $this->filesystem->normalizePath($dirName);
        $this->contentDevDir = $src;
        $targetBase = $paths->wpContent();

        $scrDirs = ["{$src}/plugins", "{$src}/themes", "{$src}/mu-plugins", "{$src}/languages"];

        $errorsOnDirs = $this->publishDirs($scrDirs, $targetBase, $operation);

        $scrFiles = array_map(
            static function (string $dropin) use ($src): string {
                return "{$src}/{$dropin}";
            },
            DropinsStep::DROPINS
        );

        $errorsOnFiles = $this->publishFiles($scrFiles, $targetBase, $operation);

        $errors = $errorsOnDirs + $errorsOnFiles;
        if ($errors > 0) {
            $this->error .= $this->operationError($errors, $operation, $src, $targetBase);
        }

        if ($errors <= 0) {
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
        return 'Development content publishing skipped.';
    }

    /**
     * @param list<string> $devContentSubfolders
     * @param string $contentDir
     * @param string $operation
     * @return int
     */
    private function publishDirs(
        array $devContentSubfolders,
        string $contentDir,
        string $operation
    ): int {

        $done = 0;
        $all = 0;

        foreach ($devContentSubfolders as $baseSource) {
            if (!is_dir($baseSource)) {
                continue;
            }

            $baseTarget = "{$contentDir}/" . basename($baseSource);
            $this->filesystem->createDir($baseTarget);

            $elements = Finder::create()
                ->in($baseSource)
                ->depth(0)
                ->ignoreVCS(true)
                ->ignoreUnreadableDirs(true)
                ->ignoreDotFiles(true);

            foreach ($elements as $element) {
                $all++;
                $target = "{$baseTarget}/" . $element->getBasename();
                $source = $element->getRealPath();
                $this->filesystem->symlinkOrCopyOperation($source, $target, $operation) and $done++;
            }
        }

        return $all - $done;
    }

    /**
     * @param array<string> $srcFiles
     * @param string $contentDir
     * @param string $operation
     * @return int
     */
    private function publishFiles(array $srcFiles, string $contentDir, string $operation): int
    {
        $done = 0;
        $all = 0;

        foreach ($srcFiles as $srcFile) {
            if (!is_file($srcFile)) {
                continue;
            }

            $all++;
            $target = "{$contentDir}/" . basename($srcFile);
            $this->filesystem->unlinkOrRemove($target);
            if ($this->filesystem->symlinkOrCopyOperation($srcFile, $target, $operation)) {
                $done++;
            }
        }

        return $all - $done;
    }

    /**
     * @param int $errors
     * @param string $operation
     * @param string $source
     * @param string $target
     * @return string
     */
    private function operationError(
        int $errors,
        string $operation,
        string $source,
        string $target
    ): string {

        $operationStr = 'both symlinking and copying';
        if ($operation === Filesystem::OP_SYMLINK) {
            $operationStr = 'symlinking';
        } elseif ($operation === Filesystem::OP_COPY) {
            $operationStr = 'copying';
        }

        $error = sprintf(
            "%s occurred while %s content-dev dir '%s' to '%s'.",
            $errors > 1 ? "{$errors} errors" : 'One error',
            $operationStr,
            $source,
            $target
        );
        if (($operation === Filesystem::OP_SYMLINK) && Platform::isWindows()) {
            $error .= "\nOn Windows make sure to run terminal as administrator.";
        }

        return $error;
    }
}
