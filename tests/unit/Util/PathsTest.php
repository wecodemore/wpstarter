<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Util\Paths;

class PathsTest extends TestCase
{
    /**
     * @test
     */
    public function testMockWorks(): void
    {
        $paths = $this->factoryPaths();

        $base = $this->fixturesPath();

        static::assertSame("{$base}/paths-root", $paths->root());
        static::assertSame("{$base}/paths-root/vendor", $paths->vendor());
        static::assertSame("{$base}/paths-root/vendor/bin", $paths->bin());
        static::assertSame("{$base}/paths-root/public/wp", $paths->wp());
        static::assertSame("{$base}/paths-root/public/wp-content", $paths->wpContent());
        static::assertSame("{$base}/paths-root/public", $paths->wpParent());
    }

    /**
     * @test
     */
    public function testToParam(): void
    {
        $paths = $this->factoryPaths();

        $base = $this->fixturesPath();

        static::assertSame("{$base}/paths-root/xxx", $paths->root('xxx'));
        static::assertSame("{$base}/paths-root/vendor/", $paths->vendor('/'));
        static::assertSame("{$base}/paths-root/vendor/bin/foo.bat", $paths->bin('foo.bat'));
        static::assertSame("{$base}/paths-root/public/wp/foo.bat", $paths->wp('/foo.bat'));
        static::assertSame("{$base}/paths-root/public/wp-content/x", $paths->wpContent('/x'));
        static::assertSame("{$base}/paths-root/public/x/", $paths->wpParent('/x/'));
    }

    /**
     * @test
     */
    public function testRelative(): void
    {
        $paths = $this->factoryPaths();

        static::assertSame(
            'public/wp-content/uploads/2018/12/25/foo.jpg',
            $paths->relativeToRoot(Paths::WP_CONTENT, '/uploads/2018/12/25/foo.jpg')
        );

        static::assertSame('public', $paths->relativeToRoot(Paths::WP_PARENT));
    }

    /**
     * @test
     */
    public function testTemplates(): void
    {
        $paths = $this->factoryPaths();

        $custom = $this->packagePath() . '/templates';
        $paths->useCustomTemplatesDir($custom);

        static::assertSame("{$custom}/index.php", $paths->template('index.php'));
    }

    /**
     * @test
     */
    public function testArrayAccessSet(): void
    {
        $paths = $this->factoryPaths();
        $paths['foo'] = $this->packagePath();

        static::assertSame($this->packagePath(), $paths['foo']);

        $this->expectException(\BadMethodCallException::class);
        $paths[Paths::ROOT] = $this->packagePath();
    }

    /**
     * @test
     */
    public function testArrayAccessGet(): void
    {
        $paths = $this->factoryPaths();

        static::assertSame($this->fixturesPath() . '/paths-root', $paths[Paths::ROOT]);

        $this->expectException(\OutOfRangeException::class);
        $paths['not set'];
    }

    /**
     * @test
     */
    public function testArrayAccessUnset(): void
    {
        $paths = $this->factoryPaths();

        $this->expectException(\BadMethodCallException::class);
        unset($paths[Paths::ROOT]);
    }
}
