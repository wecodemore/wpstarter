<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Util;

use Composer\Composer;
use Composer\Repository\RepositoryManager;
use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\ComposerPlugin;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Step\CheckPathStep;
use WeCodeMore\WpStarter\Step\ContentDevStep;
use WeCodeMore\WpStarter\Step\DropinsStep;
use WeCodeMore\WpStarter\Step\EnvExampleStep;
use WeCodeMore\WpStarter\Step\FlushEnvCacheStep;
use WeCodeMore\WpStarter\Step\IndexStep;
use WeCodeMore\WpStarter\Step\MoveContentStep;
use WeCodeMore\WpStarter\Step\MuLoaderStep;
use WeCodeMore\WpStarter\Step\VcsIgnoreCheckStep;
use WeCodeMore\WpStarter\Step\WpCliCommandsStep;
use WeCodeMore\WpStarter\Step\WpCliConfigStep;
use WeCodeMore\WpStarter\Step\WpConfigStep;
use WeCodeMore\WpStarter\Tests\DummyStep;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Tests\TestIo;
use WeCodeMore\WpStarter\Tests\TestStepOne;
use WeCodeMore\WpStarter\Tests\TestStepTwo;
use WeCodeMore\WpStarter\Util\SelectedStepsFactory;

class SelectedStepsFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function testSelectedCommandModeIsSetAccordingToFlags(): void
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

    /**
     * @test
     */
    public function testLastErrorEmptyBeforeRunning(): void
    {
        $autorun = SelectedStepsFactory::autorun();
        $command = new SelectedStepsFactory(SelectedStepsFactory::MODE_COMMAND, 'foo');

        static::assertSame('', $autorun->lastError());
        static::assertSame('', $command->lastError());
    }

    /**
     * @test
     */
    public function testLastFatalErrorEmptyBeforeRunning(): void
    {
        $autorun = SelectedStepsFactory::autorun();
        $command = new SelectedStepsFactory(SelectedStepsFactory::MODE_COMMAND, 'foo');

        static::assertSame('', $autorun->lastFatalError());
        static::assertSame('', $command->lastFatalError());
    }

    /**
     * @test
     */
    public function testOptInValidSteps(): void
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

    /**
     * @test
     *
     * @dataProvider provideStepClasses
     */
    public function testOptInValidStepsWithAlias(string $stepName, string $stepClass): void
    {
        $io = new TestIo();
        $composer = $this->factoryComposer($io);

        $locator = $this->factoryLocator(
            $this->factoryConfig(),
            new Filesystem(),
            new Io($io),
            $composer,
            $io,
            $this->factoryPaths()
        );

        $nameParts = str_split($stepName, (int)ceil(strlen($stepName) / 2));
        $alt1 = implode('_', $nameParts);
        $alt2 = implode('-', $nameParts);
        $names = [
            $alt1,
            $alt2,
            '-._.-' . $alt1,
            '-._.-' . $alt2,
            $alt1 . '--',
            $alt2 . '/',
            implode('---', $nameParts),
            implode('/', $nameParts),
            '-' . implode('//', $nameParts) . '__',
            'build.~.' . $stepName,
            'build' . $stepName,
            'build' . $alt1,
            'build' . $alt2,
            'build-' . $alt1,
            'build-' . $alt2,
            'build-' . $stepName,
            'build_' . $alt1,
            'build_' . $alt2,
            'build_' . $stepName,
            'build.' . $alt1,
            'build.' . $alt2,
            'build.' . $stepName,
            'build.~.' . $alt1,
            'build.~.' . $alt2,
            'build.~.' . $stepName,
        ];

        foreach ($names as $name) {
            $factory = new SelectedStepsFactory(
                SelectedStepsFactory::MODE_COMMAND,
                $name
            );

            $steps = $factory->selectAndFactory($locator, $composer);

            $message = "For {$name} of {$stepName} => {$stepClass}";
            static::assertCount(1, $steps, $message);
            static::assertInstanceOf($stepClass, $steps[0], $message);
            static::assertSame('', $factory->lastError(), $message);
        }
    }

    /**
     * @test
     */
    public function testOptInBothValidInvalidSteps(): void
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

    /**
     * @test
     */
    public function testOptInInvalidSteps(): void
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

    /**
     * @test
     */
    public function testOptInCommandSteps(): void
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

    /**
     * @test
     */
    public function testOptInCommandStepsWrongName(): void
    {
        $factory = new SelectedStepsFactory(SelectedStepsFactory::MODE_COMMAND, 'cmd-step');

        $config = [Config::COMMAND_STEPS => ['cmd-step' => DummyStep::class]];
        $locator = $this->factoryLocator($this->factoryConfig($config), \Mockery::mock(Io::class));
        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(0, $steps);
        static::assertStringContainsString('invalid step setting', $factory->lastError());
    }

    /**
     * @test
     */
    public function testOptInValidStepsWithSkipConfig(): void
    {
        $factory = new SelectedStepsFactory(
            SelectedStepsFactory::MODE_COMMAND,
            CheckPathStep::NAME,
            FlushEnvCacheStep::NAME
        );

        $locator = $this->factoryLocator(
            $this->factoryConfig([Config::SKIP_STEPS => [CheckPathStep::NAME]]),
            \Mockery::mock(Filesystem::class),
            \Mockery::mock(Io::class)
        );

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(1, $steps);
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[0]);
        static::assertStringContainsString('--ignore-skip-config', $factory->lastError());
    }

    /**
     * @test
     */
    public function testOptInValidStepsWithSkipButSkipConfigIgnored(): void
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

    /**
     * @test
     */
    public function testOptOutValidSteps(): void
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
            VcsIgnoreCheckStep::NAME,
            WpCliConfigStep::NAME,
            WpCliCommandsStep::NAME
        );

        $locator = $this->factoryLocator(
            $this->factoryConfig(),
            \Mockery::mock(Filesystem::class),
            \Mockery::mock(Io::class),
            $this->factoryPaths()
        );

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(1, $steps);
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[0]);
        static::assertSame('', $factory->lastError());
    }

    /**
     * @test
     */
    public function testOptOutBothValidAndInvalidSteps(): void
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

        $locator = $this->factoryLocator(
            $this->factoryConfig(),
            \Mockery::mock(Filesystem::class),
            \Mockery::mock(Io::class)
        );

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(1, $steps);
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[0]);
        static::assertStringContainsString('invalid step name', $factory->lastError());
    }

    /**
     * @test
     */
    public function testOptOutValidStepsWithSkipConfig(): void
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
                VcsIgnoreCheckStep::NAME,
                WpCliConfigStep::NAME,
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
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[0]);
        static::assertInstanceOf(WpCliCommandsStep::class, $steps[1]);
        static::assertSame('', $factory->lastError());
    }

    /**
     * @test
     */
    public function testOptOutValidStepsWithSkipConfigWithIgnoredSkipConfig(): void
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
            WpCliConfigStep::NAME,
            VcsIgnoreCheckStep::NAME
        );

        $config = [
            Config::SKIP_STEPS => [
                CheckPathStep::class,
                VcsIgnoreCheckStep::NAME,
            ],
        ];

        $locator = $this->factoryLocator(
            $this->factoryConfig($config),
            \Mockery::mock(Filesystem::class),
            \Mockery::mock(Io::class)
        );

        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertCount(3, $steps);
        static::assertInstanceOf(CheckPathStep::class, $steps[0]);
        static::assertInstanceOf(FlushEnvCacheStep::class, $steps[1]);
        static::assertInstanceOf(WpCliCommandsStep::class, $steps[2]);
        static::assertSame('', $factory->lastError());
    }

    /**
     * @test
     */
    public function testOptOutWithoutAnySteps(): void
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

    /**
     * @test
     */
    public function testListAll(): void
    {
        $flags = SelectedStepsFactory::MODE_LIST;

        $factory = new SelectedStepsFactory($flags);

        $io = new TestIo();

        $locator = $this->factoryLocator($this->factoryConfig(), new Io($io));
        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertSame([], $steps);
        static::assertSame('', $factory->lastError());
        static::assertTrue($io->hasOutputThatMatches('/available commands/i'));
        static::assertFalse($io->hasOutputThatMatches('/\* Command only/i'));
        foreach (array_keys(ComposerPlugin::defaultSteps()) as $name) {
            static::assertTrue($io->hasOutputThatMatches("~{$name}~i"));
        }
    }

    /**
     * @test
     */
    public function testListNotListExcluded(): void
    {
        $flags = SelectedStepsFactory::MODE_LIST;

        $factory = new SelectedStepsFactory($flags);

        $allSteps = ComposerPlugin::defaultSteps();

        $config = $this->factoryConfig(['skip-steps' => [EnvExampleStep::NAME]]);
        $io = new TestIo();

        $locator = $this->factoryLocator($config, new Io($io));
        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertSame([], $steps);
        static::assertSame('', $factory->lastError());
        static::assertTrue($io->hasOutputThatMatches('/available commands/i'));
        static::assertFalse($io->hasOutputThatMatches('/\* Command only/i'));
        foreach (array_keys($allSteps) as $name) {
            ($name === EnvExampleStep::NAME)
                ? static::assertFalse($io->hasOutputThatMatches("~{$name}~i"))
                : static::assertTrue($io->hasOutputThatMatches("~{$name}~i"));
        }

        static::assertTrue($io->hasOutputThatMatches("~is not included because excluded~i"));
        static::assertFalse($io->hasOutputThatMatches("~is not included because skipped~i"));
    }

    /**
     * @test
     */
    public function testListIncludeExcludedIfConfigIgnored(): void
    {
        $flags = SelectedStepsFactory::MODE_LIST | SelectedStepsFactory::IGNORE_SKIP_STEPS_CONFIG;

        $factory = new SelectedStepsFactory($flags);

        $allSteps = ComposerPlugin::defaultSteps();
        $exclude = array_rand($allSteps, 2);

        $config = $this->factoryConfig(['skip-steps' => $exclude]);
        $io = new TestIo();

        $locator = $this->factoryLocator($config, new Io($io));
        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertSame([], $steps);
        static::assertSame('', $factory->lastError());
        static::assertTrue($io->hasOutputThatMatches('/available commands/i'));
        static::assertFalse($io->hasOutputThatMatches('/\* Command only/i'));
        foreach (array_keys($allSteps) as $name) {
            static::assertTrue($io->hasOutputThatMatches("~{$name}~i"));
        }
    }

    /**
     * @test
     */
    public function testListSkipGivenWhenOptOut(): void
    {
        $flags = SelectedStepsFactory::MODE_LIST | SelectedStepsFactory::MODE_OPT_OUT;

        $allSteps = ComposerPlugin::defaultSteps();

        $factory = new SelectedStepsFactory($flags, EnvExampleStep::NAME);

        $config = $this->factoryConfig();
        $io = new TestIo();

        $locator = $this->factoryLocator($config, new Io($io));
        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertSame([], $steps);
        static::assertSame('', $factory->lastError());
        static::assertTrue($io->hasOutputThatMatches('/available commands/i'));
        static::assertFalse($io->hasOutputThatMatches('/\* Command only/i'));
        foreach (array_keys($allSteps) as $name) {
            ($name === EnvExampleStep::NAME)
                ? static::assertFalse($io->hasOutputThatMatches("~{$name}~i"))
                : static::assertTrue($io->hasOutputThatMatches("~{$name}~i"));
        }

        static::assertFalse($io->hasOutputThatMatches("~because excluded~i"));
        static::assertTrue($io->hasOutputThatMatches("~is not included because skipped~i"));
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testListIncludeCustomSteps(): void
    {
        $flags = SelectedStepsFactory::MODE_LIST;

        $allSteps = ComposerPlugin::defaultSteps();

        $factory = new SelectedStepsFactory($flags);

        require_once $this->fixturesPath() . '/TestStepOne.php';
        require_once $this->fixturesPath() . '/TestStepTwo.php';

        $config = $this->factoryConfig([
            Config::COMMAND_STEPS => ['test-step-one' => TestStepOne::class],
            Config::CUSTOM_STEPS => ['test-step-two' => TestStepTwo::class],
        ]);
        $io = new TestIo();

        $locator = $this->factoryLocator($config, new Io($io));
        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertSame([], $steps);
        static::assertSame('', $factory->lastError());
        static::assertTrue($io->hasOutputThatMatches('/test-step-one*/i'));
        static::assertTrue($io->hasOutputThatMatches('/test class with a single line doc bloc/i'));
        static::assertTrue($io->hasOutputThatMatches('/test-step-two/i'));
        static::assertFalse($io->hasOutputThatMatches('/test-step-two\*/i'));
        static::assertTrue($io->hasOutputThatMatches('/test class with a multi-line doc bloc/i'));
        static::assertFalse($io->hasOutputThatMatches('/with a second line/i'));
        static::assertFalse($io->hasOutputThatMatches('/and a third line after a space/i'));
        static::assertTrue($io->hasOutputThatMatches('/\* Command only/i'));
        foreach (array_keys($allSteps) as $name) {
            static::assertTrue($io->hasOutputThatMatches("~{$name}~i"));
        }
    }

    /**
     * @test
     */
    public function testListIncludeErrors(): void
    {
        $flags = SelectedStepsFactory::MODE_LIST;

        $factory = new SelectedStepsFactory($flags);

        $config = $this->factoryConfig([
            Config::CUSTOM_STEPS => ['test-step-invalid' => __CLASS__],
        ]);
        $io = new TestIo();

        $locator = $this->factoryLocator($config, new Io($io));
        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertSame([], $steps);
        static::assertNotFalse(stripos($factory->lastError(), 'one invalid step'));
        static::assertFalse($io->hasOutputThatMatches('/test-step-invalid/i'));
        foreach (array_keys(ComposerPlugin::defaultSteps()) as $name) {
            static::assertTrue($io->hasOutputThatMatches("~{$name}~i"));
        }
    }

    /**
     * @test
     */
    public function testListAllExcluded(): void
    {
        $flags = SelectedStepsFactory::MODE_LIST;

        $allSteps = ComposerPlugin::defaultSteps();

        $factory = new SelectedStepsFactory($flags);

        $config = $this->factoryConfig([
            Config::SKIP_STEPS => array_keys($allSteps),
        ]);
        $io = new TestIo();

        $locator = $this->factoryLocator($config, new Io($io));
        $composer = \Mockery::mock(Composer::class);

        $steps = $factory->selectAndFactory($locator, $composer);

        static::assertSame([], $steps);
        static::assertSame('', $factory->lastError());
        foreach (array_keys($allSteps) as $name) {
            static::assertFalse($io->hasOutputThatMatches("~{$name}~i"));
        }

        static::assertTrue($io->hasOutputThatMatches("~are not included because excluded~i"));
        static::assertFalse($io->hasOutputThatMatches("~are not included because skipped~i"));
    }

    /**
     * @return list<array{string, string}>
     */
    public static function provideStepClasses(): array
    {
        return [
            [CheckPathStep::NAME, CheckPathStep::class],
            [ContentDevStep::NAME, ContentDevStep::class],
            [DropinsStep::NAME, DropinsStep::class],
            [EnvExampleStep::NAME, EnvExampleStep::class],
            [FlushEnvCacheStep::NAME, FlushEnvCacheStep::class],
            [IndexStep::NAME, IndexStep::class],
            [MoveContentStep::NAME, MoveContentStep::class],
            [WpCliCommandsStep::NAME, WpCliCommandsStep::class],
            [WpCliConfigStep::NAME, WpCliConfigStep::class],
            [WpConfigStep::NAME, WpConfigStep::class],
        ];
    }
}
