<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Util\Paths;

class PathsTest extends TestCase
{
    public function testMockWorks()
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

    public function testToParam()
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

    public function testRelative()
    {
        $paths = $this->factoryPaths();

        static::assertSame(
            'public/wp-content/uploads/2018/12/25/foo.jpg',
            $paths->relativeToRoot(Paths::WP_CONTENT, '/uploads/2018/12/25/foo.jpg')
        );

        static::assertSame('public', $paths->relativeToRoot(Paths::WP_PARENT));
    }

    public function testTemplates()
    {
        $paths = $this->factoryPaths();

        $custom = $this->packagePath() . '/templates';
        $paths->useCustomTemplatesDir($custom);

        static::assertSame("{$custom}/index.php", $paths->template('index.php'));
    }

    public function testArrayAccessSet()
    {
        $paths = $this->factoryPaths();
        $paths['foo'] = $this->packagePath();

        static::assertSame($this->packagePath(), $paths['foo']);

        $this->expectException(\BadMethodCallException::class);
        $paths[Paths::ROOT] = $this->packagePath();
    }

    public function testArrayAccessGet()
    {
        $paths = $this->factoryPaths();

        static::assertSame($this->fixturesPath() . '/paths-root', $paths[Paths::ROOT]);

        $this->expectException(\OutOfRangeException::class);
        $paths['not set'];
    }

    public function testArrayAccessUnset()
    {
        $paths = $this->factoryPaths();

        $this->expectException(\BadMethodCallException::class);
        unset($paths[Paths::ROOT]);
    }
}
