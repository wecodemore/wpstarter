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
        putenv("WPSTARTER_ENV_LOADED=1");
        define('SITE_ID_CURRENT_SITE', 123);

        $bridge = WordPressEnvBridge::load();

        static::assertSame(123, $bridge['SITE_ID_CURRENT_SITE']);
        static::assertNull($bridge['ALLOW_UNFILTERED_UPLOADS']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadWithoutFileAndWithoutSkipFails()
    {
        $this->expectException(\RuntimeException::class);

        WordPressEnvBridge::load();
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testLoadFile()
    {
        $bridge = WordPressEnvBridge::load($this->fixturesPath(), 'example.env');

        static::assertSame('localhost', $bridge['DB_HOST']);
        static::assertSame('wp', $bridge['DB_NAME']);
        static::assertSame('my secret!', $bridge['DB_PASSWORD']);
        static::assertSame('xxx_', $bridge['DB_TABLE_PREFIX']);
        static::assertSame('wp_user', $bridge['DB_USER']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testConstantsOverrideEnv()
    {
        $bridge = WordPressEnvBridge::load($this->fixturesPath(), 'example.env');

        define('DB_HOST', '168.192.168.12');

        static::assertSame('168.192.168.12', $bridge['DB_HOST']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testSetupWordPress()
    {
        $bridge = WordPressEnvBridge::load($this->fixturesPath(), 'example.env');
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
        $bridge = WordPressEnvBridge::load($this->fixturesPath(), 'example.env');
        putenv('ANSWER=42');

        static::assertSame('42', $bridge['ANSWER']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testValuesCantBeChanged()
    {
        $bridge = WordPressEnvBridge::load($this->fixturesPath(), 'example.env');

        $this->expectException(\BadMethodCallException::class);
        unset($bridge['DB_HOST']);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testValuesCantBeUnset()
    {
        $bridge = WordPressEnvBridge::load($this->fixturesPath(), 'example.env');

        $this->expectException(\BadMethodCallException::class);
        $bridge['DB_HOST'] = '127.0.0.1';
    }

    /**
     * @covers \WeCodeMore\WpStarter\Env\WordPressEnvBridge
     */
    public function testTypeErrorIfWrongOffsetType()
    {
        $bridge = WordPressEnvBridge::load($this->fixturesPath(), 'example.env');

        $this->expectException(\TypeError::class);
        $bridge[12];
    }
}
