<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Config;

use WeCodeMore\WpStarter\Config\Validator;
use WeCodeMore\WpStarter\Step\CheckPathStep;
use WeCodeMore\WpStarter\Step\ContentDevStep;
use WeCodeMore\WpStarter\Step\OptionalStep;
use WeCodeMore\WpStarter\Step\WpConfigStep;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\WpCli\FileData;

class ValidatorTest extends TestCase
{
    /**
     * @see Validator::validateOverwrite()
     * @see TestCase::makeValidator()
     */
    public function testValidateOverwrite()
    {
        $validator = $this->makeValidator();

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
     * @see Validator::validateSteps()
     * @see TestCase::makeValidator()
     */
    public function testValidateSteps()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateSteps([])->notEmpty());
        static::assertFalse($validator->validateSteps(['xxx'])->notEmpty());
        static::assertFalse($validator->validateSteps(['xxx' => true])->notEmpty());
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
     * @see Validator::validateScripts()
     * @see TestCase::makeValidator()
     */
    public function testValidateScripts()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateScripts([])->notEmpty());
        static::assertFalse($validator->validateScripts([])->notEmpty());
        static::assertFalse($validator->validateScripts(['xxx'])->notEmpty());
        static::assertFalse($validator->validateScripts(2)->notEmpty());
        static::assertFalse($validator->validateScripts('xxx')->notEmpty());
        static::assertFalse($validator->validateScripts(null)->notEmpty());

        $cb1 = static function () {
        };
        $cb2 = static function () {
        };
        $cbsIn = ['pre-a' => $cb1, 'post-b' => $cb2, 'pre-' => $cb2];
        $cbsOut = ['pre-a' => [$cb1], 'post-b' => [$cb2]];
        $join = ['pre-x' => [$cb1, $cb2]];

        static::assertFalse($validator->validateScripts([$cb1, $cb2])->notEmpty());

        static::assertTrue($validator->validateScripts($cbsIn)->is($cbsOut));
        static::assertTrue($validator->validateScripts($join)->is($join));
    }

    /**
     * @see Validator::validateDropins()
     * @see TestCase::makeValidator()
     */
    public function testValidateDropins()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateDropins([])->notEmpty());
        static::assertFalse($validator->validateDropins('foo')->notEmpty());
        static::assertFalse($validator->validateDropins(2)->notEmpty());
        static::assertFalse($validator->validateDropins('xx"x')->notEmpty());
        static::assertFalse($validator->validateDropins(null)->notEmpty());

        $dir = str_replace('\\', '/', __DIR__);
        $file = str_replace('\\', '/', __FILE__);

        $input = [
            'foo',
            'dir' => $dir,
            'f"oo',
            '../foo/bar',
            'url-1' => 'https://foo/bar?x=y',
            $file,
            'meh',
            'https://username:password@127.0.0.1',
        ];

        $expected = [
            'dir' => $dir,
            'url-1' => 'https://foo/bar?x=y',
            $file => $file,
            'https://username:password@127.0.0.1' => 'https://username:password@127.0.0.1',
        ];

        static::assertSame($expected, $validator->validateDropins($input)->unwrap());
    }

    /**
     * @see Validator::validateContentDevOperation()
     * @see TestCase::makeValidator()
     */
    public function testValidateContentDevOperation()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateContentDevOperation([])->notEmpty());
        static::assertFalse($validator->validateContentDevOperation(null)->notEmpty());
        static::assertFalse($validator->validateContentDevOperation('')->notEmpty());

        static::assertTrue($validator->validateContentDevOperation(OptionalStep::ASK)
            ->is(OptionalStep::ASK));

        static::assertTrue($validator->validateContentDevOperation(false)
            ->is(ContentDevStep::OP_NONE));

        static::assertTrue($validator->validateContentDevOperation(true)
            ->is(ContentDevStep::OP_SYMLINK));

        static::assertTrue($validator->validateContentDevOperation(ContentDevStep::OP_SYMLINK)
            ->is(ContentDevStep::OP_SYMLINK));

        static::assertTrue($validator->validateContentDevOperation(ContentDevStep::OP_NONE)
            ->is(ContentDevStep::OP_NONE));

        static::assertTrue($validator->validateContentDevOperation(ContentDevStep::OP_COPY)
            ->is(ContentDevStep::OP_COPY));
    }

    /**
     * @see Validator::validateWpCliCommands()
     * @see TestCase::makeValidator()
     */
    public function testValidateWpCliCommands()
    {
        $validator = $this->makeValidator();

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
     * @see Validator::validateWpCliCommand()
     * @see TestCase::makeValidator()
     */
    public function testValidateWpCliCommand()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateWpCliCommand(null)->notEmpty());
        static::assertFalse($validator->validateWpCliCommand([])->notEmpty());
        static::assertFalse($validator->validateWpCliCommand(true)->notEmpty());
        static::assertFalse($validator->validateWpCliCommand('cli info')->notEmpty());

        static::assertTrue($validator->validateWpCliCommand('wp cli info')->is('cli info'));

        $cmd = 'wp cli info --path=./foo --format=list';
        static::assertTrue($validator->validateWpCliCommand($cmd)->is('cli info --format=list'));
    }

    /**
     * @see Validator::validateWpCliFiles()
     * @see TestCase::makeValidator()
     */
    public function testValidateWpCliFiles()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateWpCliFiles(null)->notEmpty());
        static::assertFalse($validator->validateWpCliFiles([])->notEmpty());
        static::assertFalse($validator->validateWpCliFiles(true)->notEmpty());

        $jsonFile = $this->fixturesPath() . '/cli-commands-list.json';
        static::assertFalse($validator->validateWpCliFiles($jsonFile)->notEmpty());

        $commandFile = $this->fixturesPath() . '/cli-command-file.php';
        /** @var FileData $data */
        $data = $validator->validateWpCliFiles($commandFile)->unwrap()[0];

        static::assertSame($data->file(), $commandFile);
        static::assertSame($data->args(), []);
        static::assertFalse($data->skipWordpress());
        static::assertTrue($data->valid());
    }

    /**
     * @see Validator::validateWpVersion()
     * @see TestCase::makeValidator()
     */
    public function testValidateWpVersion()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateWpVersion(null)->notEmpty());
        static::assertFalse($validator->validateWpVersion(true)->notEmpty());
        static::assertFalse($validator->validateWpVersion(true)->notEmpty());
        static::assertFalse($validator->validateWpVersion(12)->notEmpty());
        static::assertFalse($validator->validateWpVersion('10.2')->notEmpty());
        static::assertFalse($validator->validateWpVersion('4.10')->notEmpty());

        static::assertTrue($validator->validateWpVersion('1')->is('1.0.0'));
        static::assertTrue($validator->validateWpVersion('1.2')->is('1.2.0'));
        static::assertTrue($validator->validateWpVersion('1.2.32')->is('1.2.32'));
        static::assertTrue($validator->validateWpVersion('1.2.32.45')->is('1.2.32'));
        static::assertTrue($validator->validateWpVersion('1.2.32-alpha1')->is('1.2.32'));
        static::assertTrue($validator->validateWpVersion(4)->is('4.0.0'));
    }

    /**
     * @see Validator::validateBoolOrAskOrUrlOrPath()
     * @see TestCase::makeValidator()
     */
    public function testValidateBoolOrAskOrUrlOrPath()
    {
        $validator = $this->makeValidator();

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
     * @see Validator::validateUrlOrPath()
     * @see TestCase::makeValidator()
     */
    public function testValidateUrlOrPath()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateUrlOrPath('foo')->notEmpty());
        static::assertFalse($validator->validateUrlOrPath(null)->notEmpty());
        static::assertFalse($validator->validateUrlOrPath(123)->notEmpty());
        static::assertFalse($validator->validateUrlOrPath([])->notEmpty());

        $dir = str_replace('\\', '/', __DIR__);
        $url = 'http://example.com';

        static::assertTrue($validator->validateUrlOrPath($dir)->is($dir));
        static::assertTrue($validator->validateUrlOrPath($url)->is($url));
    }

    /**
     * @see Validator::validateGlobPath()
     * @see TestCase::makeValidator()
     */
    public function testValidateGlobPath()
    {
        $validator = $this->makeValidator();

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
     * @see Validator::validateFileName()
     * @see TestCase::makeValidator()
     */
    public function testValidateFileName()
    {
        $validator = $this->makeValidator();

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
     * @see Validator::validateGlobPathArray()
     * @see TestCase::makeValidator()
     */
    public function testValidateGlobPathArray()
    {
        $validator = $this->makeValidator();

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
     * @see Validator::validateInt()
     * @see TestCase::makeValidator()
     */
    public function testValidateInt()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateInt(null)->notEmpty());
        static::assertFalse($validator->validateInt('/')->notEmpty());
        static::assertFalse($validator->validateInt(true)->notEmpty());
        static::assertFalse($validator->validateInt('?123')->notEmpty());

        static::assertTrue($validator->validateInt('123')->is(123));
        static::assertTrue($validator->validateInt(123)->is(123));
        static::assertTrue($validator->validateInt(123.123)->is(123));
    }
}
