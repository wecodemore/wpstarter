<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Integration\Env;

use org\bovigo\vfs\vfsStream;
use WeCodeMore\WpStarter\Env\WordPressEnvBridge;
use WeCodeMore\WpStarter\Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class WordPressEnvBridgeTest extends TestCase
{
    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadSkippingFile()
    {
        $_ENV['WPSTARTER_ENV_LOADED'] = 1;

        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());

        static::assertNull($bridge->read('ALLOW_UNFILTERED_UPLOADS'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadFile()
    {
        $bridge = new WordPressEnvBridge();

        $bridge->load('example.env', $this->fixturesPath());

        static::assertSame('localhost', $bridge->read('DB_HOST'));
        static::assertSame('wp', $bridge->read('DB_NAME'));
        static::assertSame('my secret!', $bridge->read('DB_PASSWORD'));
        static::assertSame('xxx_', $bridge->read('DB_TABLE_PREFIX'));
        static::assertSame('wp_user', $bridge->read('DB_USER'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadFileMoreTimesDoNothing()
    {
        $bridge = new WordPressEnvBridge();

        $bridge->load('example.env', $this->fixturesPath());
        $bridge->load('more.env', $this->fixturesPath());

        static::assertSame('localhost', $bridge->read('DB_HOST'));
        static::assertNull($bridge->read('FOO'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testHttpServerVarsAreReturnedOnlyIfLoaded()
    {
        $_SERVER['HTTP_FOO'] = 'foo';
        $_SERVER['_HTTP_FOO'] = 'foo';

        $bridge = new WordPressEnvBridge();
        $bridge->load('more.env', $this->fixturesPath());

        static::assertSame('Yes', $bridge->read('HTTP_MEH'));
        static::assertNull($bridge->read('HTTP_FOO'));
        static::assertSame('foo', $bridge->read('_HTTP_FOO'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testGetEnvIsSkippedForNotLoadedVars()
    {
        putenv('PUT_THE_ENV=HERE');

        $bridge = new WordPressEnvBridge();
        $bridge->load('more.env', $this->fixturesPath());

        static::assertSame('HERE', getenv('PUT_THE_ENV'));
        static::assertNull($bridge->read('PUT_THE_ENV'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppended()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->loadAppended('more.env', $this->fixturesPath());

        static::assertSame('192.168.1.255', $bridge->read('DB_HOST'));
        static::assertSame('BAR BAR', $bridge->read('BAZ'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppendedWrongFileDoNothing()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->loadAppended('not-more.env', $this->fixturesPath());

        static::assertSame('localhost', $bridge->read('DB_HOST'));
        static::assertNull($bridge->read('BAZ'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppendedAlwaysLoadsIfLoadWasCalledAndEnvLoaded()
    {
        $_ENV['WPSTARTER_ENV_LOADED'] = 1;

        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());

        static::assertNull($bridge->read('DB_HOST'));

        $bridge->loadAppended('more.env', $this->fixturesPath());

        static::assertSame('192.168.1.255', $bridge->read('DB_HOST'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppendedNotLoadsIfLoadWasNotCalledAndEnvLoaded()
    {
        $_ENV['WPSTARTER_ENV_LOADED'] = 1;

        $bridge = new WordPressEnvBridge();

        static::assertNull($bridge->read('DB_HOST'));

        $bridge->loadAppended('more.env', $this->fixturesPath());

        static::assertNull($bridge->read('DB_HOST'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppendedDoesNotOverrideActualEnv()
    {
        $_ENV['FOO'] = "I come first.";

        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->loadAppended('more.env', $this->fixturesPath());
        $bridge->write('NEW', 'new!');

        $env = $bridge->readMany('DB_NAME', 'DB_HOST', 'FOO', 'NEW');

        static::assertSame('wp', $env['DB_NAME']);            // example.env
        static::assertSame('192.168.1.255', $env['DB_HOST']); // more.env
        static::assertSame('I come first.', $env['FOO']);     // actual.env
        static::assertSame('new!', $env['NEW']);              // offsetSet
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testSetupWordPress()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->setupWordPress();

        static::assertTrue(defined('DB_HOST'));
        static::assertSame('localhost', DB_HOST);
        static::assertSame('localhost', $bridge->read('DB_HOST'));

        static::assertTrue(defined('ALLOW_UNFILTERED_UPLOADS'));
        static::assertSame(false, ALLOW_UNFILTERED_UPLOADS);
        static::assertSame(false, $bridge->read('ALLOW_UNFILTERED_UPLOADS'));

        static::assertTrue(defined('EMPTY_TRASH_DAYS'));
        static::assertSame(12, EMPTY_TRASH_DAYS);
        static::assertSame(12, $bridge->read('EMPTY_TRASH_DAYS'));

        static::assertTrue(defined('ADMIN_COOKIE_PATH'));
        static::assertSame('/foo/bar', ADMIN_COOKIE_PATH);
        static::assertSame('/foo/bar', $bridge->read('ADMIN_COOKIE_PATH'));

        static::assertTrue(defined('WP_POST_REVISIONS'));
        static::assertSame(5, WP_POST_REVISIONS);
        static::assertSame(5, $bridge->read('WP_POST_REVISIONS'));

        static::assertTrue(defined('FS_CHMOD_DIR'));
        static::assertSame(0666, FS_CHMOD_DIR);
        static::assertSame(0666, $bridge->read('FS_CHMOD_DIR'));

        static::assertSame('xxx_', $bridge->read('DB_TABLE_PREFIX'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testArbitraryValuesUnfiltered()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $_ENV['ANSWER'] = '42';

        static::assertSame('42', $bridge->read('ANSWER'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testNewValuesCanBeAppended()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->write('ANSWER', '42!');

        static::assertSame('42!', $bridge->read('ANSWER'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadedValuesCanBeUpdated()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->write('DB_HOST', '127.0.0.255');

        static::assertSame('127.0.0.255', $bridge->read('DB_HOST'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testValuesInActualEnvCanNotBeAppended()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $_ENV['ANSWER'] = '42';

        $this->expectException(\BadMethodCallException::class);
        $bridge->write('ANSWER', '42!!!');

        static::assertSame('42', $bridge->read('ANSWER'));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testDumpCacheAndLoadFromDump()
    {
        $dir = vfsStream::setup('directory');
        $cacheFile = $dir->url() . '/cached.env.php';

        $_ENV['WP_POST_REVISIONS'] = '5';
        $_ENV['FS_CHMOD_DIR'] = '0644';

        $bridge = new WordPressEnvBridge();
        $bridge->write('FOO', 'Bar!');

        static::assertSame(5, $bridge->read('WP_POST_REVISIONS'));
        static::assertSame(0644, $bridge->read('FS_CHMOD_DIR'));
        static::assertSame('Bar!', $bridge->read('FOO'));
        static::assertSame('Bar!', getenv('FOO'));

        $bridge->dumpCached($cacheFile);

        unset($_ENV['WP_POST_REVISIONS']);
        unset($_ENV['FS_CHMOD_DIR']);
        unset($_ENV['FOO']);
        unset($_SERVER['FOO']);
        putenv('FOO');

        // Because cache
        static::assertSame(5, $bridge->read('WP_POST_REVISIONS'));
        static::assertSame(0644, $bridge->read('FS_CHMOD_DIR'));
        static::assertSame('Bar!', $bridge->read('FOO'));
        static::assertFalse(getenv('FOO'));

        \Closure::bind(
            function () {
                /** @noinspection PhpUndefinedFieldInspection */
                static::$cache = static::$loadedVars = null;
            },
            $bridge,
            WordPressEnvBridge::class
        )();

        // Cache is now remove
        static::assertNull($bridge->read('WP_POST_REVISIONS'));
        static::assertNull($bridge->read('FS_CHMOD_DIR'));
        static::assertNull($bridge->read('FOO'));

        $cachedBridge = WordPressEnvBridge::buildFromCacheDump($cacheFile);

        $_ENV['XYZ'] = 'XYZ';

        static::assertSame(5, $cachedBridge->read('WP_POST_REVISIONS'));
        static::assertSame(0644, $cachedBridge->read('FS_CHMOD_DIR'));
        static::assertTrue(defined('FS_CHMOD_DIR'));
        static::assertTrue(defined('WP_POST_REVISIONS'));
        static::assertSame(5, WP_POST_REVISIONS);
        static::assertSame(0644, FS_CHMOD_DIR);
        static::assertSame('Bar!', $bridge->read('FOO'));
        static::assertSame('Bar!', getenv('FOO'));
        static::assertFalse(defined('FOO'));
        static::assertSame('XYZ', $bridge->read('XYZ'));
    }
}
