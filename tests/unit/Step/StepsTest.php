<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests\Unit\Step;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem as ComposerFilesystem;
use WeCodeMore\WpStarter\ComposerPlugin;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Step\ScriptHaltSignal;
use WeCodeMore\WpStarter\Step\Step;
use WeCodeMore\WpStarter\Step\Steps;
use WeCodeMore\WpStarter\Step\WpConfigStep;
use WeCodeMore\WpStarter\Tests\TestCase;
use WeCodeMore\WpStarter\Tests\TestIo;
use WeCodeMore\WpStarter\Util\FileContentBuilder;
use WeCodeMore\WpStarter\Util\Filesystem;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\OverwriteHelper;
use WeCodeMore\WpStarter\Util\Salter;

class StepsTest extends TestCase
{
    /**
     * @return void
     */
    public function testScriptByAlias(): void
    {
        $script = new class ($this) {
            private static $done = false;
            /** @var TestCase */
            private static $case;

            public function __construct(TestCase $case)
            {
                self::$case = $case;
            }

            public static function run(int $result, Step $step, Locator $locator): ScriptHaltSignal
            {
                self::$case->assertSame(Step::NONE, $result);
                self::$case->assertTrue($step instanceof WpConfigStep);
                $locator->io()->write('Lorem Ipsum');
                self::$done = true;

                return ScriptHaltSignal::haltStep('test purposes');
            }

            public function done(): bool
            {
                return self::$done;
            }
        };

        $extra = [
            'wpstarter' => [
                'scripts' => ['pre-build-wp-config' => [[get_class($script), 'run']]],
            ],
        ];
        $io = new TestIo(IOInterface::VERBOSE);
        $composer = \Mockery::mock(Composer::class);
        $filesystem = new ComposerFilesystem();
        $config = $this->factoryConfig($extra['wpstarter'], $extra);
        $paths = $this->factoryPaths($extra);

        $locator = $this->factoryLocator(
            $io,
            new Io($io),
            $composer,
            new FileContentBuilder(),
            $filesystem,
            new Filesystem($filesystem),
            new Salter(),
            $config,
            $paths
        );

        $steps = Steps::commandMode($locator, $composer);
        $step = new WpConfigStep($locator);
        $steps->addStep($step);

        static::assertFalse($script->done());

        $steps->run($config, $paths);

        static::assertTrue($script->done());
        static::assertTrue($io->hasOutputThatMatches('~Lorem Ipsum~'));
        $name = $step->name();
        static::assertTrue($io->hasOutputThatMatches("~running 'pre' scripts for '{$name}'~"));
        static::assertTrue($io->hasOutputThatMatches("~halted step~"));
        static::assertTrue($io->hasOutputThatMatches("~'pre-build-wp-config'.+?is deprecated~"));
        static::assertTrue($io->hasOutputThatMatches("~please use 'pre-{$name}'~"));
    }
}
