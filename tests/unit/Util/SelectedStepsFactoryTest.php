<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use Composer\Composer;
use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Step\CheckPathStep;
use WeCodeMore\WpStarter\Step\ContentDevStep;
use WeCodeMore\WpStarter\Step\DropinsStep;
use WeCodeMore\WpStarter\Step\EnvExampleStep;
use WeCodeMore\WpStarter\Step\FlushEnvCacheStep;
use WeCodeMore\WpStarter\Step\IndexStep;
use WeCodeMore\WpStarter\Step\MoveContentStep;
use WeCodeMore\WpStarter\Step\MuLoaderStep;
use WeCodeMore\WpStarter\Step\WpCliCommandsStep;
use WeCodeMore\WpStarter\Step\WpCliConfigStep;
use WeCodeMore\WpStarter\Step\WpConfigStep;
use WeCodeMore\WpStarter\Tests\DummyStep;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\SelectedStepsFactory;

class SelectedStepsFactoryTest extends TestCase
{
    public function testSelectedCommandModeIsSetAccordingToFlags()
    {
        $factory = SelectedStepsFactory::autorun();
        static::assertFalse($factory->isSelectedCommandMode());

        $flags = SelectedStepsFactory::MODE_COMMAND | SelectedStepsFactory::MODE_OPT_OUT;
        $factory = new SelectedStepsFactory($flags);
        static::assertFalse($factory->isSelectedCommandMode());

        $flags = SelectedStepsFactory::MODE_COMMAND | SelectedStepsFactory::MODE_OPT_OUT;
        $factory = new SelectedStepsFactory($flags, 'foo', 'bar');
        static::assertFalse($factory->isSelectedCommandMode());

        $flags = SelectedStepsFactory::MODE_COMMAND;
        $factory = new SelectedStepsFactory($flags);
        static::assertFalse($factory->isSelectedCommandMode());

        $flags = SelectedStepsFactory::MODE_COMMAND;
        $factory = new SelectedStepsFactory($flags, 'foo', 'bar');
        static::assertTrue($factory->isSelectedCommandMode());

        $flags = SelectedStepsFactory::MODE_COMMAND | SelectedStepsFactory::SKIP_CUSTOM_STEPS;
        $factory = new SelectedStepsFactory($flags, 'foo', 'bar');
        static::assertTrue($factory->isSelectedCommandMode());
    }

    public function testLastErrorEmptyBeforeRunning()
    {
        $autorun = SelectedStepsFactory::autorun();
        $command = new SelectedStepsFactory(SelectedStepsFactory::MODE_COMMAND, 'foo');

        static::assertSame('', $autorun->lastError());
        static::assertSame('', $command->lastError());
    }

    public function testLastFatalErrorEmptyBeforeRunning()
    {
        $autorun = SelectedStepsFactory::autorun();
        $command = new SelectedStepsFactory(SelectedStepsFactory::MODE_COMMAND, 'foo');

        static::assertSame('', $autorun->lastFatalError());
        static::assertSame('', $command->lastFatalError());
    }

    public function testOptInValidSteps()
    {
        $factory = new SelectedStepsFactory(
            SelectedStepsFactory::MODE_COMMAND,
            CheckPathStep::NAME,
            FlushEnvCacheStep::NAME
        );

        $locator = $this->factoryLocator(
            $this->factoryConfig(),
            \Mockery::mock(Filesystem::class),
            \Mockery::mock(Io::class)
        );

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(2, $steps);
        static::assertInstanceOf(CheckPathStep::class, $steps[0]);
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[1]);
        static::assertSame('', $factory->lastError());
    }

    public function testOptInBothValidInvalidSteps()
    {
        $factory = new SelectedStepsFactory(
            SelectedStepsFactory::MODE_COMMAND,
            CheckPathStep::NAME,
            'foo'
        );

        $locator = $this->factoryLocator(
            $this->factoryConfig(),
            \Mockery::mock(Filesystem::class),
            \Mockery::mock(Io::class)
        );

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(1, $steps);
        static::assertInstanceOf(CheckPathStep::class, $steps[0]);
        static::assertStringContainsString('invalid step', $factory->lastError());
    }

    public function testOptInInvalidSteps()
    {
        $factory = new SelectedStepsFactory(
            SelectedStepsFactory::MODE_COMMAND,
            'foo',
            'bar'
        );

        $locator = $this->factoryLocator($this->factoryConfig(), \Mockery::mock(Io::class));

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(0, $steps);
        static::assertStringContainsString('invalid steps', $factory->lastError());
    }

    public function testOptInCommandSteps()
    {
        $factory = new SelectedStepsFactory(SelectedStepsFactory::MODE_COMMAND, 'dummy');

        $config = [Config::COMMAND_STEPS => ['dummy' => DummyStep::class]];
        $locator = $this->factoryLocator($this->factoryConfig($config), \Mockery::mock(Io::class));
        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(1, $steps);
        static::assertInstanceOf(DummyStep::class, $steps[0]);
        static::assertSame('', $factory->lastError());
    }

    public function testOptInCommandStepsWrongName()
    {
        $factory = new SelectedStepsFactory(SelectedStepsFactory::MODE_COMMAND, 'cmd-step');

        $config = [Config::COMMAND_STEPS => ['cmd-step' => DummyStep::class]];
        $locator = $this->factoryLocator($this->factoryConfig($config), \Mockery::mock(Io::class));
        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(0, $steps);
        static::assertStringContainsString('invalid step setting', $factory->lastError());
    }

    public function testOptInValidStepsWithSkipConfig()
    {
        $factory = new SelectedStepsFactory(
            SelectedStepsFactory::MODE_COMMAND,
            CheckPathStep::NAME,
            FlushEnvCacheStep::NAME
        );

        $locator = $this->factoryLocator(
            $this->factoryConfig([Config::SKIP_STEPS => [CheckPathStep::NAME]]),
            \Mockery::mock(Io::class)
        );

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(1, $steps);
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[0]);
        static::assertStringContainsString('--ignore-skip-config', $factory->lastError());
    }

    public function testOptInValidStepsWithSkipButSkipConfigIgnored()
    {
        $factory = new SelectedStepsFactory(
            SelectedStepsFactory::MODE_COMMAND | SelectedStepsFactory::IGNORE_SKIP_STEPS_CONFIG,
            CheckPathStep::NAME,
            FlushEnvCacheStep::NAME
        );

        $locator = $this->factoryLocator(
            $this->factoryConfig([Config::SKIP_STEPS => CheckPathStep::class]),
            \Mockery::mock(Filesystem::class),
            \Mockery::mock(Io::class)
        );

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(2, $steps);
        static::assertInstanceOf(CheckPathStep::class, $steps[0]);
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[1]);
        static::assertSame('', $factory->lastError());
    }

    public function testOptOutValidSteps()
    {
        $factory = new SelectedStepsFactory(
            SelectedStepsFactory::MODE_COMMAND | SelectedStepsFactory::MODE_OPT_OUT,
            CheckPathStep::NAME,
            WpConfigStep::NAME,
            IndexStep::NAME,
            MuLoaderStep::NAME,
            EnvExampleStep::NAME,
            DropinsStep::NAME,
            MoveContentStep::NAME,
            ContentDevStep::NAME,
            WpCliConfigStep::NAME,
            WpCliCommandsStep::NAME
        );

        $locator = $this->factoryLocator($this->factoryConfig(), \Mockery::mock(Io::class));

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(1, $steps);
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[0]);
        static::assertSame('', $factory->lastError());
    }

    public function testOptOutBothValidAndInvalidSteps()
    {
        $factory = new SelectedStepsFactory(
            SelectedStepsFactory::MODE_COMMAND | SelectedStepsFactory::MODE_OPT_OUT,
            CheckPathStep::NAME,
            WpConfigStep::NAME,
            IndexStep::NAME,
            MuLoaderStep::NAME,
            EnvExampleStep::NAME,
            DropinsStep::NAME,
            MoveContentStep::NAME,
            ContentDevStep::NAME,
            WpCliConfigStep::NAME,
            WpCliCommandsStep::NAME,
            'foo'
        );

        $locator = $this->factoryLocator($this->factoryConfig(), \Mockery::mock(Io::class));

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(1, $steps);
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[0]);
        static::assertStringContainsString('invalid step name', $factory->lastError());
    }

    public function testOptOutValidStepsWithSkipConfig()
    {
        $factory = new SelectedStepsFactory(
            SelectedStepsFactory::MODE_COMMAND | SelectedStepsFactory::MODE_OPT_OUT,
            CheckPathStep::NAME,
            WpConfigStep::NAME,
            IndexStep::NAME
        );

        $config = [
            Config::SKIP_STEPS => [
                MuLoaderStep::NAME,
                EnvExampleStep::NAME,
                DropinsStep::NAME,
                MoveContentStep::NAME,
                ContentDevStep::NAME,
                WpCliConfigStep::NAME,
            ],
        ];

        $locator = $this->factoryLocator($this->factoryConfig($config), \Mockery::mock(Io::class));

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(1, $steps);
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[0]);
        static::assertSame('', $factory->lastError());
    }

    public function testOptOutValidStepsWithSkipConfigWithIgnoredSkipConfig()
    {
        $flags = SelectedStepsFactory::MODE_COMMAND
            | SelectedStepsFactory::MODE_OPT_OUT
            | SelectedStepsFactory::IGNORE_SKIP_STEPS_CONFIG;

        $factory = new SelectedStepsFactory(
            $flags,
            WpConfigStep::NAME,
            IndexStep::NAME,
            MuLoaderStep::NAME,
            EnvExampleStep::NAME,
            DropinsStep::NAME,
            MoveContentStep::NAME,
            ContentDevStep::NAME,
            WpCliConfigStep::NAME
        );

        $config = [
            Config::SKIP_STEPS => [
                CheckPathStep::class,
            ],
        ];

        $locator = $this->factoryLocator(
            $this->factoryConfig($config),
            \Mockery::mock(Filesystem::class),
            \Mockery::mock(Io::class)
        );

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(2, $steps);
        static::assertInstanceOf(CheckPathStep::class, $steps[0]);
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[1]);
        static::assertSame('', $factory->lastError());
    }

    public function testOptOutWithoutAnySteps()
    {
        $flags = SelectedStepsFactory::MODE_COMMAND
            | SelectedStepsFactory::MODE_OPT_OUT
            | SelectedStepsFactory::IGNORE_SKIP_STEPS_CONFIG;

        $factory = new SelectedStepsFactory($flags);

        $locator = $this->factoryLocator($this->factoryConfig(), \Mockery::mock(Io::class));
        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertSame([], $steps);
        static::assertStringContainsString('was expecting one or more step', $factory->lastError());
    }
}
