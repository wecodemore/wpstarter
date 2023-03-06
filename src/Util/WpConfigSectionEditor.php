<?php

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use Composer\Util\Filesystem as ComposerFilesystem;

class WpConfigSectionEditor
{
    public const APPEND = 1;
    public const PREPEND = -1;
    public const REPLACE = 0;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var string|null
     */
    private $wpPath;

    /**
     * @var ComposerFilesystem
     */
    private $filesystem;

    /**
     * @param Paths $paths
     * @param ComposerFilesystem $filesystem
     */
    public function __construct(Paths $paths, ComposerFilesystem $filesystem)
    {
        $this->paths = $paths;
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $pathToFile
     * @param string $section
     * @param string $newContent
     * @return void
     */
    public function append(string $section, string $newContent): void
    {
        $this->edit($section, $newContent, self::APPEND);
    }

    /**
     * @param string $pathToFile
     * @param string $section
     * @param string $newContent
     * @return void
     */
    public function prepend(string $section, string $newContent): void
    {
        $this->edit($section, $newContent, self::PREPEND);
    }

    /**
     * @param string $pathToFile
     * @param string $section
     * @param string $newContent
     * @return void
     */
    public function replace(string $section, string $newContent): void
    {
        $this->edit($section, $newContent, self::REPLACE);
    }

    /**
     * @param string $pathToFile
     * @param string $section
     * @return void
     */
    public function delete(string $section): void
    {
        $this->replace($section, '');
    }

    /**
     * @param string $section
     * @return string
     */
    public function sectionContent(string $section): string
    {
        $safeSection = preg_quote(strtoupper(trim($section)), '~');

        preg_match(
            "~(?:{$safeSection}\s*\:\s*\{)(.+?)(?:\}\s*#@@/{$safeSection})~s",
            $this->currentContent(),
            $matches
        );

        $content = $matches && is_string($matches[1] ?? null) ? $matches[1] : null;
        if (!$content) {
            return '';
        }

        $lines = array_map('trim', explode("\n", trim($content)));

        return implode("\n", $lines);
    }

    /**
     * @param string $section
     * @param string $newContent
     * @param int $editMode
     * @return void
     */
    private function edit(string $section, string $newContent, int $editMode): void
    {
        $content = $this->currentContent();

        $newContentLines = array_map('rtrim', explode("\n", $newContent));
        $newContent = implode("\n    ", $newContentLines);

        $newSection = "    " . trim($newContent);
        if (trim($newContent) === '') {
            $newSection = '';
        }

        $editing = $this->wrapSectionInEditHash($newSection, $editMode);
        $isReplace = $editMode === self::REPLACE;
        if (!$isReplace && (strpos($content, $editing) !== false)) {
            return;
        }

        if (!$editing) {
            if (!$isReplace) {
                return;
            }

            $editing = PHP_EOL;
        }

        if (!$isReplace) {
            $editing = ($editMode === self::APPEND) ? '$2' . "{$editing}\n" : "\n{$editing}" . '$2';
        }

        $original = $content;
        $safeSection = preg_quote($section, '~');
        $replaced = preg_replace(
            "~({$safeSection}\s*\:\s*\{)(.+?)(\}\s*#@@/{$safeSection})~s",
            '$1' . $editing . '$3',
            $content
        );

        if ($replaced === null) {
            throw new \Exception("Failed replacing section {$section} in wp-config.php.");
        }

        if ($original === $replaced) {
            return;
        }

        $pathToFile = $this->wpConfigPath();
        if (!file_put_contents($pathToFile, $replaced)) {
            throw new \Exception("Error writing {$pathToFile} with edited {$section} section.");
        }
    }

    /**
     * @return string
     */
    private function wpConfigPath(): string
    {
        if ($this->wpPath) {
            return $this->wpPath;
        }

        $pathToFile = $this->paths->root('wp-config.php');
        if (!file_exists($pathToFile)) {
            throw new \Exception("Could not find {$pathToFile}.");
        }

        $this->wpPath = $pathToFile;

        return $pathToFile;
    }

    /**
     * @return string
     */
    private function currentContent(): string
    {
        $pathToFile = $this->wpConfigPath();
        $content = file_get_contents($pathToFile);
        if (!$content) {
            throw new \Exception("Could not read {$pathToFile} content.");
        }

        return $content;
    }

    /**
     * @param string $section
     * @param int $editMode
     * @return string
     */
    private function wrapSectionInEditHash(string $section, int $editMode): string
    {
        if ($editMode === self::REPLACE) {
            return "\n{$section}\n";
        }
        $file = '-';
        $line = -1;
        // phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        // phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
        if (is_array($trace[1] ?? null)) {
            $file = $this->filesystem->normalizePath($trace[1]['file'] ?? '-');
            $line = $trace[1]['line'] ?? -1;
        }
        $hash = md5(sprintf('%s#%s#%d', preg_replace("~\s+~", '', trim($section)), $file, $line));
        $id = sprintf('%s-%s', $this->editModeLabel($editMode), $hash);

        return "# <{$id}>\n{$section}\n# </{$id}>";
    }

    /**
     * @param int $mode
     * @return string
     */
    private function editModeLabel(int $mode): string
    {
        if ($mode === self::REPLACE) {
            return 'R';
        }

        return ($mode === self::APPEND) ? 'A' : 'P';
    }
}
