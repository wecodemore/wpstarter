<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;

class VcsIgnoreCheckStep implements OptionalStep, ConditionalStep
{
    public const NAME = 'vcsignorecheck';

    private const VCS_LABELS = ['git' => 'Git', 'hg' => 'Mercurial', 'svn' => 'Subversion'];
    private const IGNORE_SIGNATURE = '# -~ Generated by WP Starter x~-';

    /**
     * @var \WeCodeMore\WpStarter\Io\Io
     */
    private $io;

    /**
     * @var \Symfony\Component\Process\ExecutableFinder
     */
    private $executableFinder;

    /**
     * @var \WeCodeMore\WpStarter\Cli\SystemProcess
     */
    private $process;

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var \WeCodeMore\WpStarter\Util\FileContentBuilder
     */
    private $builder;

    /**
     * @var "git"|"hg"|"svn"|null
     */
    private $vcs = null;

    /**
     * @var string
     */
    private $success = '';

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->io = $locator->io();
        $this->executableFinder = $locator->executableFinder();
        $this->process = $locator->systemProcess();
        $this->filesystem = $locator->filesystem();
        $this->builder = $locator->fileContentBuilder();
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
        return $config[Config::CHECK_VCS_IGNORE]->not(false);
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $this->vcs = null;
        $this->success = '';

        $pathsToIgnore = $this->pathsToIgnore($paths, $config);

        if (!$pathsToIgnore) {
            return Step::NONE;
        }

        $root = $paths->root();

        $this->vcs = $this->determineVcs($root);
        if ($this->vcs === null) {
            $this->printNoVcsMessage($pathsToIgnore, $config);

            return Step::NONE;
        }
        $notIgnored = $this->findNotIgnored($pathsToIgnore, $paths, $config);
        ($notIgnored === null)
            ? $this->printIgnoreConfigNotFound($pathsToIgnore, $config)
            : $this->printNotIgnoredMessage($notIgnored);

        /** @psalm-suppress TypeDoesNotContainType */
        if (
            ($this->success !== '')
            && $config[Config::IS_WPSTARTER_SELECTED_COMMAND]->is(true)
        ) {
            return Step::SUCCESS;
        }

        return Step::NONE;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return $this->success;
    }

    /**
     * @return string
     */
    public function conditionsNotMet(): string
    {
        return sprintf('disabled via "%s" configuration', Config::CHECK_VCS_IGNORE);
    }

    /**
     * @param Config $config
     * @param Io $io
     * @return bool
     */
    public function askConfirm(Config $config, Io $io): bool
    {
        if ($config[Config::CHECK_VCS_IGNORE]->not(self::ASK)) {
            return true;
        }

        return $io->askConfirm(['Do you want to check VCS-ignored paths?']);
    }

    /**
     * @return string
     */
    public function skipped(): string
    {
        return 'VCS-ignored paths check skipped.';
    }

    /**
     * @param Paths $paths
     * @param Config $config
     * @return array<string, string>
     */
    private function pathsToIgnore(Paths $paths, Config $config): array
    {
        $from = $paths->root();

        $envDirName = $config[Config::ENV_DIR]->unwrap();
        $envFileName = $config[Config::ENV_FILE]->unwrapOrFallback('.env');

        /** @var list<string> $toIgnore */
        $toIgnore = [
            $paths->vendor('/'),
            $paths->wp('/'),
            $paths->wpContent('/'),
            $paths->root("{$envDirName}/{$envFileName}"),
            $paths->root('wp-config.php'),
            $paths->wpParent('wp-config.php'),
            $paths->wpParent('index.php'),
            $paths->root('wp-cli.yml'),
        ];

        /** @var string $phpEnvDirConfig */
        $phpEnvDirConfig = $config[Config::ENV_BOOTSTRAP_DIR]->unwrapOrFallback('');
        $phpEnvDir = $phpEnvDirConfig ? $paths->root($phpEnvDirConfig) : null;
        if (is_string($phpEnvDir) && is_dir($phpEnvDir)) {
            foreach (['local', 'development', 'staging', 'production'] as $env) {
                $toIgnore[] = $this->filesystem->normalizePath("{$phpEnvDir}/{$env}.php");
            }
        }
        $parsed = [];
        foreach ($toIgnore as $path) {
            if (!isset($parsed[$path]) && file_exists($path)) {
                $parsed[$path] = 1;
            }
        }

        return $parsed ? $this->findCommonParents(array_keys($parsed), $from) : [];
    }

    /**
     * @param non-empty-list<string> $pathsToIgnore
     * @param string $root
     * @return array<string, string>
     */
    private function findCommonParents(array $pathsToIgnore, string $root): array
    {
        $parsed = [];
        $parents = [];
        foreach ($this->relativizePaths($pathsToIgnore, $root) as $path) {
            $chunks = explode('/', $path);
            if (count($chunks) === 1) {
                $fullpath = "{$root}/{$path}";
                $relPath = is_dir($fullpath) ? "{$path}/" : $path;
                $parsed[$relPath] = $fullpath;
                continue;
            }
            while (count($chunks) > 1 && implode('/', $chunks) !== $root) {
                $last = array_pop($chunks);
                $parent = implode('/', $chunks);
                isset($parents[$parent]) or $parents[$parent] = [];
                $parents[$parent][] = "{$parent}/{$last}";
            }
        }

        foreach ($parents as $parent => $children) {
            $count = count($children);
            if ($count < 1) {
                continue;
            }
            if ($count > 1) {
                $fullpath = "{$root}/{$parent}";
                $relPath = is_dir($fullpath) ? "{$parent}/" : $parent;
                $parsed[$relPath] = $fullpath;
                continue;
            }
            $child = array_shift($children);
            $fullpath = "{$root}/{$child}";
            $relPath = is_dir($fullpath) ? "{$child}/" : $child;
            $parsed[$relPath] = $fullpath;
        }

        return $parsed;
    }

    /**
     * @param non-empty-list<string> $paths
     * @param string $root
     * @return list<non-empty-string>
     */
    private function relativizePaths(array $paths, string $root): array
    {
        $relative = [];
        foreach ($paths as $path) {
            if ($path === '') {
                continue;
            }
            $relPath = $this->filesystem->findShortestPath($root, $path, is_dir($path));
            if (strpos($relPath, '..') === 0) {
                continue;
            }
            (strpos($relPath, './') === 0) and $relPath = substr($relPath, 2);
            $relative[] = ($relPath !== '') ? $relPath : $path;
        }

        return $relative;
    }

    /**
     * @param string $root
     * @return "git"|"hg"|"svn"|null
     */
    private function determineVcs(string $root): ?string
    {
        if (is_dir("{$root}/.git")) {
            return 'git';
        }

        if (is_dir("{$root}/.hg")) {
            return 'hg';
        }

        if (is_dir("{$root}/.svn")) {
            return 'svn';
        }

        return null;
    }

    /**
     * @param non-empty-array<string, string> $pathsToIgnore
     * @param Config $config
     * @return void
     */
    private function printNoVcsMessage(array $pathsToIgnore, Config $config): void
    {
        if (!$this->io->isVerbose() && $config[Config::IS_WPSTARTER_SELECTED_COMMAND]->not(true)) {
            return;
        }

        $this->io->writeCommentBlock(
            'WP Starter was not able to determine the version control software in use, if any.',
            'Please do not keep under version control the following paths:',
            sprintf('"%s".', implode('", "', $pathsToIgnore)),
            sprintf(
                'To hide this message set "%s" configuration to false.',
                Config::CHECK_VCS_IGNORE
            )
        );
    }

    /**
     * @param non-empty-array<string, string> $pathsToIgnore
     * @param Config $config
     * @return void
     */
    private function printIgnoreConfigNotFound(array $pathsToIgnore, Config $config): void
    {
        if (
            ($this->vcs !== 'git')
            && !$this->io->isVerbose()
            && $config[Config::IS_WPSTARTER_SELECTED_COMMAND]->not(true)
        ) {
            return;
        }

        $vcsName = $this->vcs ? self::VCS_LABELS[$this->vcs] : '';

        $this->io->writeCommentBlock(
            "Looks like you are using {$vcsName} for version control, but "
            . 'WP Starter was not able to determine which paths are ignored, if any.',
            'Please do not keep under version control the following paths:',
            sprintf('"%s".', implode('", "', array_keys($pathsToIgnore))),
            sprintf(
                'To hide this message set "%s" configuration to false.',
                Config::CHECK_VCS_IGNORE
            )
        );
    }

    /**
     * @param list<string> $notIgnored
     * @return void
     */
    private function printNotIgnoredMessage(array $notIgnored): void
    {
        if ($notIgnored === []) {
            return;
        }

        $paths = '- ' . implode("\n- ", $notIgnored);
        $lines = explode("\n", $paths);
        $lines[] = sprintf(
            'To hide this message set "%s" configuration to false.',
            Config::CHECK_VCS_IGNORE
        );

        $this->io->writeErrorBlock(
            'Looks like you are using Git for version control, but the following paths '
            . 'are not Git-ignored:',
            ...$lines
        );
    }

    /**
     * @param non-empty-array<string, string> $pathsToIgnore
     * @param Paths $paths
     * @param Config $config
     * @return list<string>|null
     */
    private function findNotIgnored(array $pathsToIgnore, Paths $paths, Config $config): ?array
    {
        if ($this->vcs === 'git') {
            return $this->findNotGitIgnored($pathsToIgnore, $paths, $config);
        }

        if ($this->vcs === 'hg') {
            return $this->findNotHgIgnored($pathsToIgnore, $paths, $config);
        }

        return null;
    }

    /**
     * @param non-empty-array<string, string> $pathsToIgnore
     * @param Paths $paths
     * @param Config $config
     * @return list<string>|null
     */
    private function findNotGitIgnored(array $pathsToIgnore, Paths $paths, Config $config): ?array
    {
        $root = $paths->root();

        if (!file_exists("{$root}/.gitignore")) {
            return $this->maybeCreateVcsIgnore($config, $paths, $pathsToIgnore, '.gitignore');
        }

        $content = file_get_contents("{$root}/.gitignore");
        if ($content && strpos($content, self::IGNORE_SIGNATURE) !== false) {
            $this->success = "Found a WP-Starter generated .gitignore file.";

            return [];
        }

        if (!$this->executableFinder->find('git')) {
            return null;
        }

        [$status, $error] = $this->process->executeCapturing('git status');
        if (preg_match('~no commits~i', $status) || $error) {
            $this->io->writeCommentBlock(
                'Looks like you are using Git for version control, but the repository '
                . 'has no commits yet. Please make sure the following paths will be Git-ignored:',
                sprintf('"%s".', implode('", "', array_keys($pathsToIgnore))),
                sprintf(
                    'To hide this message set "%s" configuration to false.',
                    Config::CHECK_VCS_IGNORE
                )
            );

            return [];
        }

        $notIgnored = [];
        try {
            foreach ($pathsToIgnore as $relPath => $asbPath) {
                if (!$this->process->executeSilently("git check-ignore -q {$asbPath}")) {
                    $notIgnored[] = $relPath;
                }
            }

            if (!$notIgnored) {
                $this->success = "It looks like all generated and sensitive paths are Git-ignored.";
            }

            return $notIgnored;
        } catch (\Throwable $error) {
            return null;
        }
    }

    /**
     * @param non-empty-array<string, string> $pathsToIgnore
     * @param Paths $paths
     * @param Config $config
     * @return list<string>|null
     */
    private function findNotHgIgnored(array $pathsToIgnore, Paths $paths, Config $config): ?array
    {
        $root = $paths->root();

        if (!file_exists("{$root}/.hgignore")) {
            return $this->maybeCreateVcsIgnore($config, $paths, $pathsToIgnore, '.hgignore');
        }

        $content = file_get_contents("{$root}/.hgignore");
        if ($content && strpos($content, self::IGNORE_SIGNATURE) !== false) {
            $this->success = "Found a WP-Starter generated .hgignore file.";

            return [];
        }

        return null;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @param non-empty-array<string, string> $pathsToIgnore
     * @param string $file
     * @return list<string>|null
     */
    private function maybeCreateVcsIgnore(
        Config $config,
        Paths $paths,
        array $pathsToIgnore,
        string $file
    ): ?array {

        $create = $config[Config::CREATE_VCS_IGNORE_FILE]->unwrapOrFallback(true);
        if ($create === OptionalStep::ASK) {
            $create = $this->io->askConfirm([
                "The file {$file} was not found.",
                'Would you like WP Starter creating it for you?',
            ]);
        }

        if (!$create) {
            return null;
        }

        $content = implode("\n", array_keys($pathsToIgnore));

        $built = $this->builder->build(
            $paths,
            $file,
            ['WPSTARTER_IGNORED_PATHS' => $content]
        );

        $built = rtrim($built) . "\n\n" . self::IGNORE_SIGNATURE;

        $filepath = $paths->root($file);

        if (!$this->filesystem->writeContent($built, $filepath)) {
            $this->io->writeError("Creation of '{$filepath}' failed.");
        }

        $this->success = "'{$filepath}' written.";

        return [];
    }
}
