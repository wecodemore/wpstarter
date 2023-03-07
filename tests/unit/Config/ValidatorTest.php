<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Config;

use WeCodeMore\WpStarter\Step\CheckPathStep;
use WeCodeMore\WpStarter\Step\OptionalStep;
use WeCodeMore\WpStarter\Step\WpConfigStep;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Cli\WpCliFileData;
use WeCodeMore\WpStarter\Util\Filesystem;

class ValidatorTest extends TestCase
{
    /**
     * @test
     */
    public function testValidateOverwrite(): void
    {
        $validator = $this->factoryValidator();

        $ask = OptionalStep::ASK;

        static::assertTrue($validator->validateOverwrite($ask)->is($ask));
        static::assertFalse($validator->validateOverwrite('foo')->notEmpty());
        static::assertTrue($validator->validateOverwrite(false)->is(false));
        static::assertTrue($validator->validateOverwrite(true)->is(true));
        static::assertTrue($validator->validateOverwrite(1)->is(true));
        static::assertTrue($validator->validateOverwrite('no')->is(false));
        static::assertTrue($validator->validateOverwrite('yes')->is(true));

        $paths = [__DIR__ . '/*.php'];
        static::assertTrue($validator->validateOverwrite($paths)->is($paths));
    }

    /**
     * @test
     */
    public function testValidateSteps(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateSteps([])->notEmpty());
        static::assertFalse($validator->validateSteps(2)->notEmpty());
        static::assertFalse($validator->validateSteps('ccc')->notEmpty());
        static::assertFalse($validator->validateSteps(null)->notEmpty());

        $steps = [
            CheckPathStep::NAME => CheckPathStep::class,
            WpConfigStep::NAME => WpConfigStep::class,
        ];
        static::assertTrue($validator->validateSteps($steps)->is($steps));
    }

    /**
     * @test
     */
    public function testValidateScripts(): void
    {
        $validator = $this->factoryValidator();

        static::assertTrue($validator->validateScripts([])->is([]));
        static::assertTrue($validator->validateScripts([])->is([]));
        static::assertFalse($validator->validateScripts(['xxx'])->notEmpty());
        static::assertFalse($validator->validateScripts(2)->notEmpty());
        static::assertFalse($validator->validateScripts('xxx')->notEmpty([]));
        static::assertTrue($validator->validateScripts(null)->is([]));

        $cbsInErr = ['pre-a' => ['a_func'], 'post-b' => ['b_func'], 'pre-' => ['a_function']];
        $cbsInOk = ['pre-a' => ['a_func'], 'post-b' => ['b_func']];
        $cbsInOkString = ['pre-a' => 'a_func', 'post-b' => ['b_func']];
        $cbsOutExpected = ['pre-a' => ['a_func'], 'post-b' => ['b_func']];
        $join = ['pre-x' => ['a_func', 'b_func']];

        static::assertSame([], $validator->validateScripts($cbsInErr)->unwrapOrFallback([]));
        static::assertSame($cbsOutExpected, $validator->validateScripts($cbsInOk)->unwrap());
        static::assertSame($cbsOutExpected, $validator->validateScripts($cbsInOkString)->unwrap());
        static::assertSame($join, $validator->validateScripts($join)->unwrap());
    }

    /**
     * @test
     */
    public function testValidateDropins(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateDropins([])->notEmpty());
        static::assertFalse($validator->validateDropins('foo')->notEmpty());
        static::assertFalse($validator->validateDropins(2)->notEmpty());
        static::assertFalse($validator->validateDropins('xx"x')->notEmpty());
        static::assertFalse($validator->validateDropins(null)->notEmpty());

        $input = [
            'foo',
            'dir' => __DIR__,
            'f"oo',
            '../foo/bar',
            'url-1' => 'https://foo/bar?x=y',
            __FILE__,
            'meh',
            'https://username:password@127.0.0.1',
            'something' => 'https://username:password@127.0.0.1',
            'https://username:password@127.0.0.1/foo',
        ];

        $expected = [
            'dir' => str_replace('\\', '/', __DIR__),
            'url-1' => 'https://foo/bar?x=y',
            basename(__FILE__) => str_replace('\\', '/', __FILE__),
            'something' => 'https://username:password@127.0.0.1',
            'foo' => 'https://username:password@127.0.0.1/foo',
        ];

        static::assertSame($expected, $validator->validateDropins($input)->unwrap());
    }

    /**
     * @test
     */
    public function testValidateContentDevOperation(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateContentDevOperation([])->notEmpty());
        static::assertFalse($validator->validateContentDevOperation(null)->notEmpty());
        static::assertFalse($validator->validateContentDevOperation('')->notEmpty());

        static::assertTrue($validator->validateContentDevOperation(OptionalStep::ASK)
            ->is(OptionalStep::ASK));

        static::assertTrue($validator->validateContentDevOperation(false)
            ->is(Filesystem::OP_NONE));

        static::assertTrue($validator->validateContentDevOperation(true)
            ->is(Filesystem::OP_AUTO));

        static::assertTrue($validator->validateContentDevOperation(Filesystem::OP_SYMLINK)
            ->is(Filesystem::OP_SYMLINK));

        static::assertTrue($validator->validateContentDevOperation(Filesystem::OP_NONE)
            ->is(Filesystem::OP_NONE));

        static::assertTrue($validator->validateContentDevOperation(Filesystem::OP_COPY)
            ->is(Filesystem::OP_COPY));
    }

    /**
     * @test
     */
    public function testValidateWpCliCommands(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateWpCliCommands(null)->notEmpty());
        static::assertFalse($validator->validateWpCliCommands('foo')->notEmpty());
        static::assertFalse($validator->validateWpCliCommands([])->notEmpty());
        static::assertFalse($validator->validateWpCliCommands(true)->notEmpty());

        $phpList = $this->fixturesPath() . '/cli-commands-list.php';
        $actualFromPhp = $validator->validateWpCliCommands($phpList);
        static::assertTrue($actualFromPhp->is(['cli version', 'cli info']));

        $jsonList = $this->fixturesPath() . '/cli-commands-list.json';
        $actualFromJson = $validator->validateWpCliCommands($jsonList);
        static::assertTrue($actualFromJson->is(['cli version', 'cli info']));

        $actual = ['wp cli version', 'wp cli info'];
        $expected = ['cli version', 'cli info'];
        static::assertTrue($validator->validateWpCliCommands($actual)->is($expected));
    }

    /**
     * @test
     */
    public function testValidateWpCliCommand(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateWpCliCommand(null)->notEmpty());
        static::assertFalse($validator->validateWpCliCommand([])->notEmpty());
        static::assertFalse($validator->validateWpCliCommand(true)->notEmpty());
        static::assertFalse($validator->validateWpCliCommand('cli info')->notEmpty());

        static::assertTrue($validator->validateWpCliCommand('wp cli info')->is('cli info'));

        $cmd = 'wp cli info --path=./foo --format=list';
        static::assertTrue($validator->validateWpCliCommand($cmd)->is('cli info --format=list'));
    }

    /**
     * @test
     */
    public function testValidateWpCliFiles(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateWpCliFiles(null)->notEmpty());
        static::assertFalse($validator->validateWpCliFiles([])->notEmpty());
        static::assertFalse($validator->validateWpCliFiles(true)->notEmpty());

        $jsonFile = $this->fixturesPath() . '/cli-commands-list.json';
        static::assertFalse($validator->validateWpCliFiles($jsonFile)->notEmpty());

        $commandFile = $this->fixturesPath() . '/cli-command-file.php';
        /** @var WpCliFileData $data */
        $data = $validator->validateWpCliFiles($commandFile)->unwrap()[0];

        static::assertSame($data->file(), $commandFile);
        static::assertSame($data->args(), []);
        static::assertFalse($data->skipWordpress());
        static::assertTrue($data->valid());
    }

    /**
     * @test
     */
    public function testValidateWpVersion(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateWpVersion(null)->notEmpty());
        static::assertFalse($validator->validateWpVersion(true)->notEmpty());
        static::assertFalse($validator->validateWpVersion(true)->notEmpty());

        static::assertTrue($validator->validateWpVersion('1')->is('1.0.0'));
        static::assertTrue($validator->validateWpVersion('1.2')->is('1.2.0'));
        static::assertTrue($validator->validateWpVersion('1.2.32')->is('1.2.32'));
        static::assertTrue($validator->validateWpVersion('1.2.32.45')->is('1.2.32'));
        static::assertTrue($validator->validateWpVersion('1.2.32-alpha1')->is('1.2.32'));
        static::assertTrue($validator->validateWpVersion(4)->is('4.0.0'));
    }

    /**
     * @test
     */
    public function testValidateBoolOrAskOrUrlOrPath(): void
    {
        $validator = $this->factoryValidator();

        $ask = OptionalStep::ASK;
        $google = 'https://example.com';
        $dir = str_replace('\\', '/', __DIR__);

        static::assertTrue($validator->validateBoolOrAskOrUrlOrPath(true)->is(true));
        static::assertTrue($validator->validateBoolOrAskOrUrlOrPath(false)->is(false));
        static::assertTrue($validator->validateBoolOrAskOrUrlOrPath('yes')->is(true));
        static::assertTrue($validator->validateBoolOrAskOrUrlOrPath('no')->is(false));

        static::assertTrue($validator->validateBoolOrAskOrUrlOrPath($ask)->is($ask));

        static::assertTrue($validator->validateBoolOrAskOrUrlOrPath($google)->is($google));
        static::assertTrue($validator->validateBoolOrAskOrUrlOrPath($dir)->is($dir));

        static::assertFalse($validator->validateBoolOrAskOrUrlOrPath('foo')->notEmpty());
        static::assertFalse($validator->validateBoolOrAskOrUrlOrPath(12)->notEmpty());
    }

    /**
     * @test
     */
    public function testValidateUrlOrPath(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateUrlOrPath('foo')->notEmpty());
        static::assertFalse($validator->validateUrlOrPath(null)->notEmpty());
        static::assertFalse($validator->validateUrlOrPath(123)->notEmpty());
        static::assertFalse($validator->validateUrlOrPath([])->notEmpty());

        $dir = str_replace('\\', '/', __DIR__);
        $url = 'https://example.com';

        static::assertTrue($validator->validateUrlOrPath($dir)->is($dir));
        static::assertTrue($validator->validateUrlOrPath($url)->is($url));
    }

    /**
     * @test
     */
    public function testValidateGlobPath(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateGlobPath('f!oo')->notEmpty());
        static::assertFalse($validator->validateGlobPath('fo"o/*')->notEmpty());
        static::assertFalse($validator->validateGlobPath(123)->notEmpty());

        static::assertTrue($validator->validateGlobPath('some/*.*')->is('some/*.*'));
        static::assertTrue($validator->validateGlobPath('some/*.php')->is('some/*.php'));
        static::assertTrue($validator->validateGlobPath('../foo/*.*')->is('../foo/*.*'));
        static::assertTrue($validator->validateGlobPath('./foo')->is('./foo'));
        static::assertTrue($validator->validateGlobPath('*/*.*')->is('*/*.*'));
        static::assertTrue($validator->validateGlobPath('**/**/*.txt')->is('**/**/*.txt'));
    }

    /**
     * @test
     */
    public function testValidateFileName(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateFileName(1)->notEmpty());
        static::assertFalse($validator->validateFileName(true)->notEmpty());
        static::assertFalse($validator->validateFileName('foo/bar')->notEmpty());
        static::assertFalse($validator->validateFileName('foo\bar')->notEmpty());
        static::assertFalse($validator->validateFileName('foo\*')->notEmpty());
        static::assertFalse($validator->validateFileName('foo?')->notEmpty());
        static::assertTrue($validator->validateFileName('foo.php')->is('foo.php'));
        static::assertTrue($validator->validateFileName('.env')->is('.env'));
    }

    /**
     * @test
     */
    public function testValidateGlobPathArray(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateGlobPathArray(null)->notEmpty());
        static::assertFalse($validator->validateGlobPathArray('/')->notEmpty());
        static::assertFalse($validator->validateGlobPathArray(true)->notEmpty());
        static::assertFalse($validator->validateGlobPathArray([])->notEmpty());
        static::assertFalse($validator->validateGlobPathArray(['f!oo'])->notEmpty());

        $paths = ['foo', '../bar', 'f!oo', 'baz/*.*'];

        static::assertTrue($validator->validateGlobPathArray($paths)
            ->is(['foo', '../bar', 'baz/*.*']));
    }

    /**
     * @test
     */
    public function testValidateInt(): void
    {
        $validator = $this->factoryValidator();

        static::assertFalse($validator->validateInt(null)->notEmpty());
        static::assertFalse($validator->validateInt('/')->notEmpty());
        static::assertFalse($validator->validateInt(true)->notEmpty());
        static::assertFalse($validator->validateInt('?123')->notEmpty());

        static::assertTrue($validator->validateInt('123')->is(123));
        static::assertTrue($validator->validateInt(123)->is(123));
        static::assertTrue($validator->validateInt(123.123)->is(123));
    }
}
