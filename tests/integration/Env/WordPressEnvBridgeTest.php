<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Integration\Env;

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
        define('SITE_ID_CURRENT_SITE', 123);

        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());

        static::assertSame(123, $bridge['SITE_ID_CURRENT_SITE']);
        static::assertNull($bridge['ALLOW_UNFILTERED_UPLOADS']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadFile()
    {
        $bridge = new WordPressEnvBridge();

        $bridge->load('example.env', $this->fixturesPath());

        static::assertSame('localhost', $bridge['DB_HOST']);
        static::assertSame('wp', $bridge['DB_NAME']);
        static::assertSame('my secret!', $bridge['DB_PASSWORD']);
        static::assertSame('xxx_', $bridge['DB_TABLE_PREFIX']);
        static::assertSame('wp_user', $bridge['DB_USER']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadFileMoreTimesDoNothing()
    {
        $bridge = new WordPressEnvBridge();

        $bridge->load('example.env', $this->fixturesPath());
        $bridge->load('more.env', $this->fixturesPath());

        static::assertSame('localhost', $bridge['DB_HOST']);
        static::assertNull($bridge['FOO']);
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

        static::assertSame('Yes', $bridge['HTTP_MEH']);
        static::assertNull($bridge['HTTP_FOO']);
        static::assertSame('foo', $bridge['_HTTP_FOO']);
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
        static::assertNull($bridge['PUT_THE_ENV']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppended()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->loadAppended('more.env', $this->fixturesPath());

        static::assertSame('192.168.1.255', $bridge['DB_HOST']);
        static::assertSame('BAR BAR', $bridge['BAZ']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppendedWrongFileDoNothing()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge->loadAppended('not-more.env', $this->fixturesPath());

        static::assertSame('localhost', $bridge['DB_HOST']);
        static::assertNull($bridge['BAZ']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppendedAlwaysLoadsIfLoadWasCalledAndEnvLoaded()
    {
        $_ENV['WPSTARTER_ENV_LOADED'] = 1;

        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());

        static::assertNull($bridge['DB_HOST']);

        $bridge->loadAppended('more.env', $this->fixturesPath());

        static::assertSame('192.168.1.255', $bridge['DB_HOST']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadAppendedNotLoadsIfLoadWasNotCalledAndEnvLoaded()
    {
        $_ENV['WPSTARTER_ENV_LOADED'] = 1;

        $bridge = new WordPressEnvBridge();

        static::assertNull($bridge['DB_HOST']);

        $bridge->loadAppended('more.env', $this->fixturesPath());

        static::assertNull($bridge['DB_HOST']);
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
        $bridge['NEW'] = 'new!';

        static::assertSame('wp', $bridge['DB_NAME']);            // example.env
        static::assertSame('192.168.1.255', $bridge['DB_HOST']); // more.env
        static::assertSame('I come first.', $bridge['FOO']);     // actual.env
        static::assertSame('new!', $bridge['NEW']);               // offsetSet
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testConstantsOverrideEnv()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());

        define('DB_HOST', '168.192.168.12');

        static::assertSame('168.192.168.12', $bridge['DB_HOST']);
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
        static::assertSame('localhost', $bridge['DB_HOST']);

        static::assertTrue(defined('ALLOW_UNFILTERED_UPLOADS'));
        static::assertSame(false, ALLOW_UNFILTERED_UPLOADS);
        static::assertSame(false, $bridge['ALLOW_UNFILTERED_UPLOADS']);

        static::assertTrue(defined('EMPTY_TRASH_DAYS'));
        static::assertSame(12, EMPTY_TRASH_DAYS);
        static::assertSame(12, $bridge['EMPTY_TRASH_DAYS']);

        static::assertTrue(defined('ADMIN_COOKIE_PATH'));
        static::assertSame('/foo/bar', ADMIN_COOKIE_PATH);
        static::assertSame('/foo/bar', $bridge['ADMIN_COOKIE_PATH']);

        static::assertTrue(defined('WP_POST_REVISIONS'));
        static::assertSame(5, WP_POST_REVISIONS);
        static::assertSame(5, $bridge['WP_POST_REVISIONS']);

        static::assertTrue(defined('FS_CHMOD_DIR'));
        static::assertSame(0666, FS_CHMOD_DIR);
        static::assertSame(0666, $bridge['FS_CHMOD_DIR']);

        static::assertSame('xxx_', $GLOBALS['table_prefix']);
        static::assertSame('xxx_', $bridge['DB_TABLE_PREFIX']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testArbitraryValuesUnfiltered()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $_ENV['ANSWER'] = '42';

        static::assertSame('42', $bridge['ANSWER']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testNewValuesCanBeAppended()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge['ANSWER'] = '42!';

        static::assertSame('42!', $bridge['ANSWER']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadedValuesCanBeUpdated()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());
        $bridge['DB_HOST'] = '127.0.0.255';

        static::assertSame('127.0.0.255', $bridge['DB_HOST']);
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
        $bridge['ANSWER'] = '42!!!';

        static::assertSame('42', $bridge['ANSWER']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testValuesCanNotBeUnset()
    {
        $bridge = new WordPressEnvBridge();
        $bridge->load('example.env', $this->fixturesPath());

        $this->expectException(\BadMethodCallException::class);
        unset($bridge['DB_HOST']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testTypeErrorIfWrongOffsetType()
    {
        $bridge = new WordPressEnvBridge();
        $this->expectException(\TypeError::class);
        $bridge[12];
    }
}
