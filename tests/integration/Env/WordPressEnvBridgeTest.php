<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadSkippingFile(): void
    {
        $_ENV['WPSTARTER_ENV_LOADED'] = 1;

        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());

        static::assertNull($bridge->read('ALLOW_UNFILTERED_UPLOADS'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadFile(): void
    {
        $bridge = new WordPressEnvBridge();

        $bridge->load('example.env', $this->fixturesPath());

        static::assertSame('localhost', $bridge->read('DB_HOST'));
        static::assertSame('wp', $bridge->read('DB_NAME'));
        static::assertSame('foo&bar!baz<qux', $bridge->read('DB_PASSWORD'));
        static::assertSame('xxx_', $bridge->read('DB_TABLE_PREFIX'));
        static::assertSame('wp_user', $bridge->read('DB_USER'));
        static::assertSame('', $bridge->read('COOKIE_DOMAIN'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadFileMoreTimesDoNothing(): void
    {
        $bridge = new WordPressEnvBridge();

        $bridge->load('example.env', $this->fixturesPath());
        $bridge->load('more.env', $this->fixturesPath());

        static::assertSame('localhost', $bridge->read('DB_HOST'));
        static::assertNull($bridge->read('FOO'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testHttpServerVarsAreReturnedOnlyIfLoaded(): void
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
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testGetEnvIsSkippedForNotLoadedVars(): void
    {
        putenv('PUT_THE_ENV=HERE');

        $bridge = new WordPressEnvBridge();
        $bridge->load('more.env', $this->fixturesPath());

        static::assertSame('HERE', getenv('PUT_THE_ENV'));
        static::assertNull($bridge->read('PUT_THE_ENV'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppended(): void
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->loadAppended('more.env', $this->fixturesPath());

        static::assertSame('192.168.1.255', $bridge->read('DB_HOST'));
        static::assertSame('BAR BAR', $bridge->read('BAZ'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppendedWrongFileDoNothing(): void
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->loadAppended('not-more.env', $this->fixturesPath());

        static::assertSame('localhost', $bridge->read('DB_HOST'));
        static::assertNull($bridge->read('BAZ'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppendedAlwaysLoadsIfLoadWasCalledAndEnvLoaded(): void
    {
        $_ENV['WPSTARTER_ENV_LOADED'] = 1;

        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());

        static::assertNull($bridge->read('DB_HOST'));

        $bridge->loadAppended('more.env', $this->fixturesPath());

        static::assertSame('192.168.1.255', $bridge->read('DB_HOST'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppendedNotLoadsIfLoadWasNotCalledAndEnvLoaded(): void
    {
        $_ENV['WPSTARTER_ENV_LOADED'] = 1;

        $bridge = new WordPressEnvBridge();

        static::assertNull($bridge->read('DB_HOST'));

        $bridge->loadAppended('more.env', $this->fixturesPath());

        static::assertNull($bridge->read('DB_HOST'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppendedDoesNotOverrideActualEnv(): void
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
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testSetupWordPress(): void
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->setupConstants();

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
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testArbitraryValuesUnfiltered(): void
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $_ENV['ANSWER'] = '42';

        static::assertSame('42', $bridge->read('ANSWER'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testNewValuesCanBeAppended(): void
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->write('ANSWER', '42!');

        static::assertSame('42!', $bridge->read('ANSWER'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadedValuesCanBeUpdated(): void
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->write('DB_HOST', '127.0.0.255');

        static::assertSame('127.0.0.255', $bridge->read('DB_HOST'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testValuesInActualEnvCanNotBeAppended(): void
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $_ENV['ANSWER'] = '42';

        $this->expectException(\BadMethodCallException::class);
        $bridge->write('ANSWER', '42!!!');

        static::assertSame('42', $bridge->read('ANSWER'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     *
     *  phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    public function testDumpCacheAndLoadFromDump(): void
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity
        $dir = vfsStream::setup('directory');
        $cacheFile = $dir->url() . '/cached.env.php';

        $_ENV['WP_POST_REVISIONS'] = '7';
        $_ENV['FS_CHMOD_DIR'] = '0644';

        $bridge = new WordPressEnvBridge();
        $bridge->loadFile($this->fixturesPath() . '/example.env');
        $bridge->write('FOO', 'Bar!');

        static::assertFalse($bridge->hasCachedValues());

        // Bridge works with actual env...
        static::assertSame(7, $bridge->read('WP_POST_REVISIONS'));
        static::assertSame(0644, $bridge->read('FS_CHMOD_DIR'));
        // ...and works with loaded env...
        static::assertSame('wp', $bridge->read('DB_NAME'));
        static::assertSame('Awesome!', $bridge->read('MY_AWESOME_VAR'));
        // and also works with "manual" added env.
        static::assertSame('Bar!', $bridge->read('FOO'));
        static::assertSame('Bar!', getenv('FOO'));
        static::assertSame('Bar!', $_ENV['FOO'] ?? '');

        $loadedVars = WordPressEnvBridge::loadedVars();

        $bridge->setupConstants();
        $bridge->dumpCached($cacheFile);

        static::assertTrue(file_exists($cacheFile));

        $oldCache = null;

        $cleanLoaded = \Closure::bind(
            /** @bound */
            function (bool $setCache) use (&$oldCache) {
                $setCache and $oldCache = static::$cache;
                static::$cache = [];
                static::$loadedVars = null;
                putenv('SYMFONY_DOTENV_VARS');
            },
            $bridge,
            WordPressEnvBridge::class
        );
        $cleanLoaded(true);

        static::assertSame([], WordPressEnvBridge::loadedVars());
        static::assertIsArray($oldCache);

        $cleanLoaded(false);

        // Cleanup some loaded env...
        putenv('MY_BAD_VAR');
        unset($_ENV['MY_BAD_VAR']);
        unset($_SERVER['MY_BAD_VAR']);
        putenv('DB_HOST');
        unset($_ENV['DB_HOST']);
        unset($_SERVER['DB_HOST']);
        putenv('DB_NAME');
        unset($_ENV['DB_NAME']);
        unset($_SERVER['DB_NAME']);
        // ...and prove it is clean
        static::assertSame(false, getenv('MY_BAD_VAR'));
        static::assertNull($_ENV['MY_BAD_VAR'] ?? null);
        static::assertNull($_ENV['MY_BAD_VAR'] ?? null);
        static::assertSame(false, getenv('DB_HOST'));
        static::assertNull($_ENV['DB_HOST'] ?? null);
        static::assertNull($_ENV['DB_HOST'] ?? null);
        static::assertSame(false, getenv('DB_NAME'));
        static::assertNull($_ENV['DB_NAME'] ?? null);
        static::assertNull($_ENV['DB_NAME'] ?? null);

        $cachedBridge = WordPressEnvBridge::buildFromCacheDump($cacheFile);
        $cachedContent = file_get_contents($cacheFile);

        static::assertFalse($cachedBridge->isWpSetup());
        static::assertTrue($cachedBridge->hasCachedValues());

        $newCache = \Closure::bind(
            /** @bound */
            function (): array {
                return static::$cache;
            },
            $cachedBridge,
            WordPressEnvBridge::class
        )();

        static::assertSame($loadedVars, WordPressEnvBridge::loadedVars());
        static::assertSame($oldCache, $newCache);

        // These variables were accessed via read() and should be part of the dump
        static::assertStringContainsString("putenv('MY_AWESOME_VAR=Awesome!');", $cachedContent);
        static::assertStringContainsString("putenv('DB_NAME=wp');", $cachedContent);
        static::assertStringContainsString("define('DB_NAME', 'wp');", $cachedContent);
        // ... and these variables were NOT accessed via read() but still should be part of the dump
        static::assertStringContainsString("putenv('MY_BAD_VAR=Bad!');", $cachedContent);
        static::assertStringContainsString("putenv('EMPTY_TRASH_DAYS=12');", $cachedContent);
        static::assertStringContainsString("define('EMPTY_TRASH_DAYS', 12);", $cachedContent);

        // WP constants are set for actual env, accessed loaded env, and not accessed loaded env
        static::assertTrue(defined('WP_POST_REVISIONS'));
        static::assertTrue(defined('DB_NAME'));
        static::assertTrue(defined('DB_HOST'));
        static::assertTrue(defined('EMPTY_TRASH_DAYS'));
        // ...and non-WP constants are not defined.
        static::assertFalse(defined('FOO'));

        $_ENV['XYZ'] = 'XYZ';

        // Actual env is still there
        static::assertSame('7', $_ENV['WP_POST_REVISIONS'] ?? null);
        static::assertSame(7, WP_POST_REVISIONS);
        static::assertSame('0644', $_ENV['FS_CHMOD_DIR'] ?? null);
        static::assertSame(0644, FS_CHMOD_DIR);
        static::assertSame('Bar!', getenv('FOO'));
        static::assertSame('Bar!', $_ENV['FOO'] ?? null);
        static::assertSame('Bar!', $_SERVER['FOO'] ?? null);

        // Loaded env can be read, we proved they were not there before cache
        static::assertSame('Bad!', getenv('MY_BAD_VAR'));
        static::assertSame('Bad!', $_ENV['MY_BAD_VAR'] ?? null);
        static::assertSame('Bad!', $_SERVER['MY_BAD_VAR'] ?? null);
        static::assertSame('localhost', getenv('DB_HOST'));
        static::assertSame('localhost', $_ENV['DB_HOST'] ?? null);
        static::assertSame('localhost', $_SERVER['DB_HOST'] ?? null);

        // read() on cached bridge should work with both actual env and loaded env...
        static::assertSame(7, $cachedBridge->read('WP_POST_REVISIONS'));
        static::assertSame(0644, $cachedBridge->read('FS_CHMOD_DIR'));
        static::assertSame('Bar!', $cachedBridge->read('FOO'));
        static::assertSame('Bad!', $cachedBridge->read('MY_BAD_VAR'));
        static::assertSame('localhost', $cachedBridge->read('DB_HOST'));
        // ...and it should still be able to read things from actual env set after cache was built
        static::assertSame('XYZ', $cachedBridge->read('XYZ'));
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testsSetupConstants(): void
    {
        $this->deleteCacheFile();

        $cacheFile = $this->fixturesPath() . '/cached.env.php';

        if (file_exists($cacheFile)) {
            static::markTestSkipped('Cache is already written.');
        }

        $_ENV['WP_POST_REVISIONS'] = '7';
        $_ENV['FS_CHMOD_DIR'] = '0644';

        $bridge = new WordPressEnvBridge();
        $bridge->loadFile($this->fixturesPath() . '/example.env');
        $bridge->write('FOO', 'Bar!');

        $bridge->write(
            'WP_STARTER_ENV_TO_CONST',
            'PLUGIN_CONFIG_ONE,PLUGIN_CONFIG_TWO:INT,PLUGIN_CONFIG_THREE:BOOL,PLUGIN_CONFIG_FOUR'
        );

        $bridge->setupConstants();

        static::assertTrue(defined('DB_HOST'));
        static::assertTrue(defined('DB_NAME'));
        static::assertTrue(defined('DB_PASSWORD'));
        static::assertTrue(defined('WP_POST_REVISIONS'));
        static::assertTrue(defined('FS_CHMOD_DIR'));

        static::assertTrue(defined('PLUGIN_CONFIG_ONE'));
        static::assertTrue(defined('PLUGIN_CONFIG_TWO'));
        static::assertTrue(defined('PLUGIN_CONFIG_THREE'));
        static::assertTrue(defined('PLUGIN_CONFIG_FOUR'));
        static::assertFalse(defined('PLUGIN_CONFIG_FIVE'));

        static::assertSame(7, WP_POST_REVISIONS);
        static::assertSame(0644, FS_CHMOD_DIR);
        static::assertSame('on', SUNRISE);

        static::assertSame(2, PLUGIN_CONFIG_TWO);
        static::assertTrue(PLUGIN_CONFIG_THREE);
        static::assertSame('4', PLUGIN_CONFIG_FOUR);

        static::assertTrue($bridge->isWpSetup());
        static::assertFalse($bridge->hasCachedValues());

        $bridge->dumpCached($cacheFile);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     * @depends testsSetupConstants
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    public function testLoadCacheFromScratch(): void
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity

        $cacheFile = $this->fixturesPath() . '/cached.env.php';
        if (!file_exists($cacheFile)) {
            static::markTestSkipped('Cache file was not written.');
        }

        static::assertTrue(file_exists($cacheFile));

        $cachedBridge = WordPressEnvBridge::buildFromCacheDump($cacheFile);

        $deleted = $this->deleteCacheFile();

        static::assertFalse($cachedBridge->isWpSetup());
        static::assertTrue($cachedBridge->hasCachedValues());

        // WP constants are defined, no matter if from actual or loaded env.
        static::assertTrue(defined('DB_HOST'));
        static::assertTrue(defined('DB_NAME'));
        static::assertTrue(defined('DB_PASSWORD'));
        static::assertTrue(defined('WP_POST_REVISIONS'));
        static::assertTrue(defined('FS_CHMOD_DIR'));
        static::assertSame(7, WP_POST_REVISIONS);
        static::assertSame(0644, FS_CHMOD_DIR);
        static::assertSame("", COOKIE_DOMAIN);
        static::assertSame("on", SUNRISE);

        // Variables from actual env are not set in env in the dump file...
        static::assertFalse(getenv('WP_POST_REVISIONS'));
        static::assertNull($_ENV['WP_POST_REVISIONS'] ?? null);
        static::assertFalse(getenv('FS_CHMOD_DIR'));
        static::assertNull($_ENV['FS_CHMOD_DIR'] ?? null);
        static::assertFalse(getenv('FOO'));
        static::assertNull($_ENV['FOO'] ?? null);
        // but because there were accessed, cache still contains them.
        static::assertSame(7, $cachedBridge->read('WP_POST_REVISIONS'));
        static::assertSame(0644, $cachedBridge->read('FS_CHMOD_DIR'));
        static::assertSame('Bar!', $cachedBridge->read('FOO'));

        // These loaded env vars were accessed in previous test (via setupWordPress()).
        static::assertSame('xxx_', getenv('DB_TABLE_PREFIX'));
        static::assertSame('xxx_', $_ENV['DB_TABLE_PREFIX'] ?? null);
        static::assertSame('xxx_', $_SERVER['DB_TABLE_PREFIX'] ?? null);
        static::assertSame('xxx_', $cachedBridge->read('DB_TABLE_PREFIX'));
        static::assertSame('wp', getenv('DB_NAME'));
        static::assertSame('wp', $_ENV['DB_NAME'] ?? null);
        static::assertSame('wp', $_SERVER['DB_NAME'] ?? null);
        static::assertSame('wp', $cachedBridge->read('DB_NAME'));
        static::assertSame('', getenv('COOKIE_DOMAIN'));
        static::assertSame('', $_ENV['COOKIE_DOMAIN'] ?? null);
        static::assertSame('', $_SERVER['COOKIE_DOMAIN'] ?? null);
        static::assertSame('', $cachedBridge->read('COOKIE_DOMAIN'));
        static::assertSame('', getenv('COOKIE_DOMAIN'));
        static::assertSame('on', $_ENV['SUNRISE'] ?? null);
        static::assertSame('on', $_SERVER['SUNRISE'] ?? null);
        static::assertSame('on', $cachedBridge->read('SUNRISE'));

        // These loaded env vars were NOT accessed in previous test, but they can be accessed now.
        static::assertSame('Bad!', getenv('MY_BAD_VAR'));
        static::assertSame('Bad!', $_ENV['MY_BAD_VAR'] ?? null);
        static::assertSame('Bad!', $_SERVER['MY_BAD_VAR'] ?? null);
        static::assertSame('Bad!', $cachedBridge->read('MY_BAD_VAR'));

        if (!$deleted) {
            static::markTestIncomplete('Warning: Cache file was not deleted.');
        }
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadWpEnvironmentTypeFromWpEnvWithAlias(): void
    {
        $_ENV['WP_ENV'] = 'PREPROD';
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->setupConstants();

        static::assertSame('preprod', $bridge->determineEnvType());
        static::assertTrue(defined('WP_ENVIRONMENT_TYPE'));
        static::assertSame('staging', WP_ENVIRONMENT_TYPE);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testSetDifferentEnvForWpAndWpStarter(): void
    {
        $_ENV['WP_ENV'] = 'something_very_custom';
        $_ENV['WP_ENVIRONMENT_TYPE'] = 'development';
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->setupConstants();

        static::assertTrue(defined('WP_ENV'));
        static::assertTrue(defined('WP_ENVIRONMENT_TYPE'));
        static::assertSame('something_very_custom', WP_ENV);
        static::assertSame('development', WP_ENVIRONMENT_TYPE);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadWpEnvironmentTypeFromWpEnvWhenStartingWithValue(): void
    {
        $_ENV['WP_ENVIRONMENT_TYPE'] = 'PREPROD-US-1';
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->setupConstants();

        static::assertTrue(defined('WP_ENVIRONMENT_TYPE'));
        static::assertTrue(defined('WP_ENV'));
        static::assertSame('preprod-us-1', WP_ENV);
        static::assertSame('preprod-us-1', $bridge->determineEnvType());
        static::assertSame('staging', WP_ENVIRONMENT_TYPE);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadWpEnvironmentTypeFromWpEnvWhenEndingWithValue(): void
    {
        $_ENV['WP_ENVIRONMENT_TYPE'] = 'My.Production';
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->setupConstants();

        static::assertSame('my.production', $bridge->determineEnvType());
        static::assertTrue(defined('WP_ENVIRONMENT_TYPE'));
        static::assertSame('production', WP_ENVIRONMENT_TYPE);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadWpEnvironmentTypeFromWpEnvWhenValueInTheMiddle(): void
    {
        $_ENV['WP_ENVIRONMENT_TYPE'] = 'my_dev_one';
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->setupConstants();

        static::assertSame('my_dev_one', $bridge->determineEnvType());
        static::assertTrue(defined('WP_ENVIRONMENT_TYPE'));
        static::assertSame('development', WP_ENVIRONMENT_TYPE);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testCustomWpEnvironmentThatCantBeMapped(): void
    {
        $_ENV['WP_ENVIRONMENT_TYPE'] = 'my_devone';
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->setupConstants();

        static::assertSame('my_devone', $bridge->determineEnvType());
        static::assertTrue(defined('WP_ENVIRONMENT_TYPE'));
        static::assertSame('production', WP_ENVIRONMENT_TYPE);
    }

    /**
     * @test
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testCustomWpEnvironmentWithCustomMapping(): void
    {
        $_ENV['WP_ENV'] = 'THIS_CANT_BE_MAPPED';
        $_ENV['WP_ENVIRONMENT_TYPE'] = 'dev';
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->setupConstants();

        static::assertSame('this_cant_be_mapped', $bridge->determineEnvType());
        static::assertTrue(defined('WP_ENVIRONMENT_TYPE'));
        static::assertSame('development', WP_ENVIRONMENT_TYPE);
    }

    /**
     * @return bool
     */
    private function deleteCacheFile(): bool
    {
        $cacheFile = $this->fixturesPath() . '/cached.env.php';

        $deleted = true;
        @unlink($cacheFile);
        if (file_exists($cacheFile)) {
            // Try again
            usleep(2500);
            @unlink($cacheFile);
            $deleted = !file_exists($cacheFile);
        }

        return $deleted;
    }
}
