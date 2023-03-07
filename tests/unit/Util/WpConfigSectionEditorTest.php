<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Util\WpConfigSectionEditor;

class WpConfigSectionEditorTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetWpConfigFile();
    }

    /**
     * @test
     */
    public function testSectionContent(): void
    {
        $editor = $this->factorySectionEditor();

        static::assertSame('/** This is the section one */', $editor->sectionContent('ONE'));
        static::assertSame('/** This is the section one */', $editor->sectionContent('one'));
        static::assertSame('/** This is the section two */', $editor->sectionContent(' two'));
        static::assertSame('/** This is the section two */', $editor->sectionContent('two '));
        static::assertSame('/** This is the section two */', $editor->sectionContent(' TWO '));
    }

    /**
     * @test
     */
    public function testEditSection(bool $repeat = false): void
    {
        $editor = $this->factorySectionEditor();
        $newLines = "New line 1\nNew line 2";

        $editor->append('ONE', $newLines);
        $editor->prepend('ONE', $newLines);

        $currentContent = $editor->sectionContent('ONE');
        $lines = explode("\n", $currentContent);

        static::assertNotFalse(preg_match('~^# <(P\-.+?)>$~', array_shift($lines), $matches));
        static::assertSame('New line 1', array_shift($lines));
        static::assertSame('New line 2', array_shift($lines));
        static::assertSame("# </{$matches[1]}>", array_shift($lines));

        static::assertSame('/** This is the section one */', array_shift($lines));

        static::assertNotFalse(preg_match('~^# <(A\-.+?)>$~', array_shift($lines), $matches));
        static::assertSame('New line 1', array_shift($lines));
        static::assertSame('New line 2', array_shift($lines));
        static::assertSame("# </{$matches[1]}>", array_shift($lines));

        if ($repeat) {
            static::assertSame(2, substr_count($currentContent, 'New line 1'));
            static::assertSame(2, substr_count($currentContent, 'New line 2'));
        }

        if (!$repeat) {
            $this->testEditSection(true);
        }
    }

    /**
     * @test
     */
    public function testEditSectionHash(): void
    {
        $editor = $this->factorySectionEditor();

        $this->doAppend($editor, 'TWO', 'Hello');
        $this->doAppend($editor, 'TWO', 'Hello ');
        $this->doAppend($editor, 'TWO', 'World');
        $this->doAppend($editor, 'TWO', ' Hello');
        $this->doAppend($editor, 'TWO', 'World');
        $this->doAppend($editor, 'TWO', ' Hello ');

        $currentContent = $editor->sectionContent('TWO');
        $lines = explode("\n", $currentContent);

        static::assertSame('/** This is the section two */', array_shift($lines));

        static::assertNotFalse(preg_match('~^# <(A\-.+?)>$~', array_shift($lines), $matches));
        static::assertSame('Hello', array_shift($lines));
        static::assertSame("# </{$matches[1]}>", array_shift($lines));
        static::assertNotFalse(preg_match('~^# <(A\-.+?)>$~', array_shift($lines), $matches));
        static::assertSame('World', array_shift($lines));
        static::assertSame("# </{$matches[1]}>", array_pop($lines));
        static::assertSame([], $lines);
    }

    /**
     * @test
     */
    public function testReplaceSection(bool $repeat = false): void
    {
        $editor = $this->factorySectionEditor();
        $newLines = "New line 1\nNew line 2";

        $editor->replace('TWO', $newLines);
        $editor->replace('THREE', $newLines);
        $editor->append('THREE', $newLines);

        $currentContentTwo = $editor->sectionContent('TWO');
        $currentContentThree = $editor->sectionContent('THREE');
        $linesTwo = explode("\n", $currentContentTwo);
        $linesThree = explode("\n", $currentContentThree);

        static::assertSame('New line 1', array_shift($linesTwo));
        static::assertSame('New line 2', array_pop($linesTwo));
        static::assertSame([], $linesTwo);

        static::assertSame('New line 1', array_shift($linesThree));
        static::assertSame('New line 2', array_shift($linesThree));
        static::assertNotFalse(preg_match('~^# <(A\-.+?)>$~', array_shift($linesThree), $matches));
        static::assertSame('New line 1', array_shift($linesThree));
        static::assertSame('New line 2', array_shift($linesThree));
        static::assertSame("# </{$matches[1]}>", array_pop($linesThree));
        static::assertSame([], $linesThree);

        if ($repeat) {
            static::assertSame(1, substr_count($currentContentTwo, 'New line 1'));
            static::assertSame(1, substr_count($currentContentTwo, 'New line 2'));
            static::assertSame(2, substr_count($currentContentThree, 'New line 1'));
            static::assertSame(2, substr_count($currentContentThree, 'New line 2'));
        }

        if (!$repeat) {
            $this->testReplaceSection(true);
        }
    }

    /**
     * @param WpConfigSectionEditor $editor
     * @param string $key
     * @param string $content
     * @return void
     */
    private function doAppend(WpConfigSectionEditor $editor, string $key, string $content): void
    {
        $editor->append($key, $content);
    }

    /**
     * @return WpConfigSectionEditor
     */
    private function factorySectionEditor(): WpConfigSectionEditor
    {
        return new WpConfigSectionEditor($this->factoryPaths(), new Filesystem());
    }

    /**
     * @return void
     */
    private function resetWpConfigFile(): void
    {
        file_put_contents(
            $this->fixturesPath() . '/paths-root/wp-config.php',
            <<<PHP
<?php

ONE : {
    /** This is the section one */
} #@@/ONE

TWO : {
    /** This is the section two */
} #@@/TWO


THREE : {
    /** This is the section three */
} #@@/THREE

PHP
        );
    }
}
