<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Config;

use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\Config\Validator;
use WeCodeMore\WpStarter\Step\CheckPathStep;
use WeCodeMore\WpStarter\Step\ContentDevStep;
use WeCodeMore\WpStarter\Step\OptionalStep;
use WeCodeMore\WpStarter\Step\WpConfigStep;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Util\OverwriteHelper;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\WpCli\FileData;

class ValidatorTest extends TestCase
{
    private function makeValidator(
        array $extra = [],
        string $vendorDir = __DIR__,
        string $binDir = __DIR__
    ): Validator {

        $config = \Mockery::mock(\Composer\Config::class);
        $config->shouldReceive('get')->with('vendor-dir')->andReturn($vendorDir);
        $config->shouldReceive('get')->with('bin-dir')->andReturn($binDir);
        $composer = \Mockery::mock(\Composer\Composer::class);
        $composer->shouldReceive('getConfig')->andReturn($config);
        $composer->shouldReceive('getPackage->getExtra')->andReturn($extra);

        $filesystem = new Filesystem();

        $paths = new Paths($composer, $filesystem);

        return new Validator($paths, $filesystem);
    }

    public function testValidateOverwrite()
    {
        $validator = $this->makeValidator();

        $ask = OptionalStep::ASK;
        $hard = OverwriteHelper::HARD;

        static::assertTrue($validator->validateOverwrite($ask)->is($ask));
        static::assertTrue($validator->validateOverwrite($hard)->is($hard));
        static::assertFalse($validator->validateOverwrite('foo')->notEmpty());
        static::assertTrue($validator->validateOverwrite(false)->is(false));
        static::assertTrue($validator->validateOverwrite(true)->is(true));
        static::assertTrue($validator->validateOverwrite(1)->is(true));
        static::assertTrue($validator->validateOverwrite('no')->is(false));
        static::assertTrue($validator->validateOverwrite('yes')->is(true));

        $paths = [__DIR__ . '/*.php'];
        static::assertTrue($validator->validateOverwrite($paths)->is($paths));
    }

    public function testValidateSteps()
    {
        $validator = $this->makeValidator();

        static::assertTrue($validator->validateSteps([])->is([]));
        static::assertTrue($validator->validateSteps(['xxx'])->is([]));
        static::assertTrue($validator->validateSteps('')->is([]));
        static::assertTrue($validator->validateSteps(null)->is([]));

        $steps = [
            CheckPathStep::NAME => CheckPathStep::class,
            WpConfigStep::NAME => WpConfigStep::class,
        ];
        static::assertTrue($validator->validateSteps($steps)->is($steps));
    }

    public function testValidateScripts()
    {
        $validator = $this->makeValidator();

        static::assertTrue($validator->validateScripts([])->is([]));
        static::assertTrue($validator->validateScripts([])->is([]));
        static::assertTrue($validator->validateScripts(['xxx'])->is([]));
        static::assertTrue($validator->validateScripts('')->is([]));
        static::assertTrue($validator->validateScripts(null)->is([]));

        $cb1 = static function () {
        };
        $cb2 = static function () {
        };
        $cbsIn = ['pre-a' => $cb1, 'post-b' => $cb2, 'pre-' => $cb2];
        $cbsOut = ['pre-a' => [$cb1], 'post-b' => [$cb2]];
        $join = ['pre-x' => [$cb1, $cb2]];

        static::assertTrue($validator->validateScripts($cbsIn)->is($cbsOut));
        static::assertTrue($validator->validateScripts([$cb1, $cb2])->is([]));
        static::assertTrue($validator->validateScripts($join)->is($join));
    }

    public function testValidateContentDevOperation()
    {
        $validator = $this->makeValidator();

        static::assertTrue($validator->validateContentDevOperation([])
            ->is(ContentDevStep::OP_NONE));
        static::assertTrue($validator->validateContentDevOperation(null)
            ->is(ContentDevStep::OP_NONE));
        static::assertTrue($validator->validateContentDevOperation('')
            ->is(ContentDevStep::OP_NONE));
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

    public function testValidateWpCliCommands()
    {
        $validator = $this->makeValidator();

        static::assertTrue($validator->validateWpCliCommands(null)->is([]));
        static::assertTrue($validator->validateWpCliCommands('foo')->is([]));
        static::assertTrue($validator->validateWpCliCommands([])->is([]));
        static::assertTrue($validator->validateWpCliCommands(true)->is([]));

        $phpList = $this->fixturesPath() . '/cli-commands-list.php';
        $actualFromPhp = $validator->validateWpCliCommands($phpList);
        static::assertTrue($actualFromPhp->is(['cli version', 'cli info']));

        $jsonList = $this->fixturesPath() . '/cli-commands-list.json';
        $actualFromJson = $validator->validateWpCliCommands($jsonList);
        static::assertTrue($actualFromJson->is(['cli version', 'cli info']));

        $actual = ['cli version', 'wp cli info'];
        $expected = ['cli version', 'cli info'];
        static::assertTrue($validator->validateWpCliCommands($actual)->is($expected));
    }

    public function testValidateWpCliCommand()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateWpCliCommand(null)->notEmpty());
        static::assertFalse($validator->validateWpCliCommand([])->notEmpty());
        static::assertFalse($validator->validateWpCliCommand(true)->notEmpty());

        static::assertTrue($validator->validateWpCliCommand('wp cli info')->is('cli info'));
        static::assertTrue($validator->validateWpCliCommand('cli info')->is('cli info'));

        $cmd = 'wp cli info --path=./foo --format=list';
        static::assertTrue($validator->validateWpCliCommand($cmd)->is('cli info --format=list'));
    }

    public function testValidateWpCliFiles()
    {
        $validator = $this->makeValidator();

        static::assertTrue($validator->validateWpCliFiles(null)->is([]));
        static::assertTrue($validator->validateWpCliFiles([])->is([]));
        static::assertTrue($validator->validateWpCliFiles(true)->is([]));

        $commandFile = $this->fixturesPath() . '/cli-command-file.php';
        /** @var FileData $data */
        $data = $validator->validateWpCliFiles($commandFile)->unwrap()[0];

        static::assertSame($data->file(), $commandFile);
        static::assertSame($data->args(), []);
        static::assertFalse($data->skipWordpress());
        static::assertTrue($data->valid());

        $jsonFile = $this->fixturesPath() . '/cli-commands-list.json';
        static::assertTrue($validator->validateWpCliFiles($jsonFile)->is([]));
    }

    public function testValidateWpVersion()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateWpVersion(null)->notEmpty());
        static::assertFalse($validator->validateWpVersion(true)->notEmpty());
        static::assertFalse($validator->validateWpVersion(true)->notEmpty());

        static::assertTrue($validator->validateWpVersion('1')->is('1.0.0'));
        static::assertTrue($validator->validateWpVersion('1.2')->is('1.2.0'));
        static::assertTrue($validator->validateWpVersion('1.2.32')->is('1.2.32'));
        static::assertTrue($validator->validateWpVersion('1.2.32.45')->is('1.2.32'));
        static::assertTrue($validator->validateWpVersion('1.2.32-alpha1')->is('1.2.32'));
        static::assertTrue($validator->validateWpVersion(4)->is('4.0.0'));
        static::assertFalse($validator->validateWpVersion(12)->notEmpty());
        static::assertFalse($validator->validateWpVersion('123')->notEmpty());
    }

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

    public function testValidateGlobPath()
    {
        $validator = $this->makeValidator();

        static::assertFalse($validator->validateGlobPath('foo')->notEmpty());
        static::assertFalse($validator->validateGlobPath('foo/*')->notEmpty());

        $dir = str_replace('\\', '/', __DIR__);
        $file = str_replace('\\', '/', __FILE__);

        static::assertTrue($validator->validateGlobPath("{$dir}/*.*")->is("{$dir}/*.*"));
        static::assertTrue($validator->validateGlobPath("{$dir}/*.php")->is("{$dir}/*.php"));
        static::assertTrue($validator->validateGlobPath($dir)->is($dir));
        static::assertTrue($validator->validateGlobPath($file)->is($file));
    }

    public function testValidatePathArray()
    {
        $validator = $this->makeValidator();

        static::assertTrue($validator->validatePathArray(null)->is([]));
        static::assertTrue($validator->validatePathArray('/')->is([]));
        static::assertTrue($validator->validatePathArray(true)->is([]));
        static::assertTrue($validator->validatePathArray([])->is([]));

        $dir = str_replace('\\', '/', __DIR__);
        $file = str_replace('\\', '/', __FILE__);
        $paths = [$dir, $file, 'foo'];
        static::assertTrue($validator->validatePathArray($paths)->is([$dir, $file]));
    }

    public function testValidateGlobPathArray()
    {
        $validator = $this->makeValidator();

        static::assertTrue($validator->validateGlobPathArray(null)->is([]));
        static::assertTrue($validator->validateGlobPathArray('/')->is([]));
        static::assertTrue($validator->validateGlobPathArray(true)->is([]));
        static::assertTrue($validator->validateGlobPathArray([])->is([]));

        $dir = str_replace('\\', '/', __DIR__);
        $file = str_replace('\\', '/', __FILE__);
        $paths = [$dir, $file, 'foo', "{$dir}/*.*"];

        static::assertTrue($validator->validateGlobPathArray($paths)
            ->is([$dir, $file, "{$dir}/*.*"]));
    }
}