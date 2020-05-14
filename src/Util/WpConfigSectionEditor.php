<?php

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

class WpConfigSectionEditor
{
    const APPEND = 1;
    const PREPEND = -1;
    const REPLACE = 0;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var string|null
     */
    private $wpPath;

    /**
     * @var string|null
     */
    private $content = null;

    /**
     * @param \Composer\Util\Filesystem $filesystem
     * @param Paths $paths
     */
    public function __construct(Paths $paths)
    {
        $this->paths = $paths;
    }

    /**
     * @param string $pathToFile
     * @param string $section
     * @param string $newContent
     * @return void
     */
    public function append(string $section, string $newContent)
    {
        $this->edit($section, $newContent, self::APPEND);
    }

    /**
     * @param string $pathToFile
     * @param string $section
     * @param string $newContent
     * @return void
     */
    public function prepend(string $section, string $newContent)
    {
        $this->edit($section, $newContent, self::PREPEND);
    }

    /**
     * @param string $pathToFile
     * @param string $section
     * @param string $newContent
     * @return void
     */
    public function replace(string $section, string $newContent)
    {
        $this->edit($section, $newContent, self::REPLACE);
    }

    /**
     * @param string $pathToFile
     * @param string $section
     * @return void
     */
    public function delete(string $section)
    {
        $this->replace($section, '');
    }

    /**
     * @param string $section
     * @param string $newContent
     * @param int $editMode
     * @return void
     */
    private function edit(string $section, string $newContent, int $editMode)
    {
        $content = $this->currentContent();

        $newContentLines = array_map('rtrim', explode("\n", $newContent));
        $newContent = implode("\n    ", $newContentLines);

        $newSection = "    " . trim($newContent);
        if (trim($newContent) === '') {
            $newSection = '';
        }

        $isReplace = $editMode === self::REPLACE;
        ($newSection && $isReplace) and $newSection = "\n{$newSection}\n";

        if (!$newSection) {
            if (!$isReplace) {
                return;
            }

            $newSection = PHP_EOL;
        }

        if (!$isReplace) {
            $newSection = $editMode === self::APPEND
                ? '$2' . "{$newSection}\n"
                : "\n{$newSection}" . '$2';
        }

        $original = $content;
        $safeSection = preg_quote($section, '~');
        $replaced = preg_replace(
            "~({$safeSection}\s*\:\s*\{)(.+)(\}\s*#@@/{$safeSection})~s",
            '$1' . $newSection . '$3',
            $content
        );

        if ($replaced === null || ($original === $replaced)) {
            throw new \Exception("Failed replacing section {$section} in wp-config.php.");
        }

        $pathToFile = $this->wpConfigPath();
        if (!file_put_contents($pathToFile, $replaced)) {
            throw new \Exception("Error writing {$pathToFile} with edited {$section} section.");
        }

        $this->content = $replaced;
    }

    /**
     * @return string
     */
    private function wpConfigPath(): string
    {
        if ($this->wpPath) {
            return $this->wpPath;
        }

        $pathToFile = $this->paths->wpParent('wp-config.php');
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

        $this->content = $content;

        return $content;
    }
}
