<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Script\Event;
use WeCodeMore\WpStarter\Utils\Activator;
use WeCodeMore\WpStarter\Utils\WpVersion;
use WeCodeMore\WpStarter\Utils\Config;
use WeCodeMore\WpStarter\Utils\FileBuilder;
use WeCodeMore\WpStarter\Utils\Filesystem;
use WeCodeMore\WpStarter\Utils\IO;
use WeCodeMore\WpStarter\Utils\OverwriteHelper;
use WeCodeMore\WpStarter\Utils\Paths;
use WeCodeMore\WpStarter\Utils\Stepper;
use WeCodeMore\WpStarter\Step;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class ComposerPlugin implements PluginInterface, EventSubscriberInterface, CommandProvider
{

    const EXTRA_KEY = 'wpstarter';
    const EXTRA_KEY_OVERRIDE = 'wpstarter-override';

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \WeCodeMore\WpStarter\Utils\IO
     */
    private $io;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Paths
     */
    private $paths;

    /**
     * @var \WeCodeMore\WpStarter\Utils\Config
     */
    private $config;

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'run',
            'post-update-cmd'  => 'run',
        ];
    }

    /**
     * @inheritdoc
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $wpVersionDiscover = new WpVersion($composer, $io);
        $wpVersion = $wpVersionDiscover->discover();

        // If no or wrong WP ver found do nothing, so run() will show an error not findind config
        if (!$wpVersion) {
            return;
        }

        $activator = new Activator($composer, $io, $wpVersion);

        $this->composer = $composer;
        $this->paths = $activator->paths();
        $this->config = $activator->config();
        $this->io = $activator->io();
    }

    /**
     * @inheritdoc
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function getCommands()
    {
        return [new WpStarterCommand()];
    }

    /**
     * Run WP Starter installation adding all the steps to Builder and launching steps processing.
     *
     * It is possible to provide the names of steps to run.
     *
     * @param Event|null $event
     * @param array $stepsOnly
     * @return void
     */
    public function run(Event $event = null, $stepsOnly = [])
    {
        if (
            !$this->config instanceof Config
            || !$this->io instanceof IO
            || !$this->paths instanceof Paths
        ) {
            $this->io->error('Error running WP Starter command.');
            $this->io->error('WordPress not found or found in a too old version.');

            return;
        }

        $overwrite = new OverwriteHelper($this->config, $this->io, $this->paths);
        $stepper = new Stepper($this->io, $overwrite);

        if (!$stepper->allowed($this->config, $this->paths)) {
            $this->io->block([
                'WP Starter installation CANCELED.',
                'wp-config.php was found in root folder and your overwrite settings',
                'do not allow to proceed.',
            ], 'yellow');

            return;
        }

        $stepsOnly = array_filter($stepsOnly, 'is_string');

        $stepClasses = array_merge([
            Step\CheckPathStep::NAME   => Step\CheckPathStep::class,
            Step\WPConfigStep::NAME    => Step\WPConfigStep::class,
            Step\IndexStep::NAME       => Step\IndexStep::class,
            Step\MuLoaderStep::NAME    => Step\MuLoaderStep::class,
            Step\EnvExampleStep::NAME  => Step\EnvExampleStep::class,
            Step\DropinsStep::NAME     => Step\DropinsStep::class,
            Step\GitignoreStep::NAME   => Step\GitignoreStep::class,
            Step\MoveContentStep::NAME => Step\MoveContentStep::class,
            Step\ContentDevStep::NAME  => Step\ContentDevStep::class,
        ], $this->config[Config::CUSTOM_STEPS]);

        $filesystem = new Filesystem();
        $fileBuilder = new FileBuilder();

        $hasWpCliStep = $hasRoboStep = false;
        $stepsAdded = [];

        foreach ($stepClasses as $stepName => $stepClass) {

            if (
                !$stepName
                || ($stepsOnly && !in_array($stepName, $stepsOnly, true))
                || in_array($stepName, $stepsAdded, true)
            ) {
                continue;
            }

            $step = $this->factoryStep($stepClass, $filesystem, $fileBuilder);

            if ($step->name() === $stepName) {
                $stepName === Step\WpCliCommandsStep::NAME and $hasWpCliStep = true;
                $stepName === Step\RoboStep::NAME and $hasRoboStep = true;
                $stepper->addStep($step);
                $stepsAdded[] = $stepName;
            }
        }

        $this->createExecutors($hasWpCliStep, $hasRoboStep, $stepper);

        $this->logo();

        $stepper->run($this->paths, $this->config[Config::VERBOSITY]);
    }

    /**
     * @param $stepClass
     * @param Filesystem $filesystem
     * @param FileBuilder $fileBuilder
     * @return Step\StepInterface
     */
    private function factoryStep($stepClass, Filesystem $filesystem, FileBuilder $fileBuilder)
    {
        if (
            !is_string($stepClass)
            || !is_subclass_of($stepClass, Step\StepInterface::class, true)
        ) {
            return new Step\NullStep();
        }

        $step = null;

        switch (true) {
            case (method_exists($stepClass, 'instance')):
                /** @var callable $factory */
                $factory = [$stepClass, 'instance'];
                $step = $factory($this->io, $filesystem, $fileBuilder);
                break;
            case (is_subclass_of($stepClass, Step\FileCreationStepInterface::class, true)):
                return new $stepClass($this->io, $filesystem, $fileBuilder);
            case (is_subclass_of($stepClass, Step\FileStepInterface::class, true)):
                return new $stepClass($this->io, $filesystem);
        }

        $step or $step = new $stepClass($this->io);

        return $step instanceof Step\StepInterface ? $step : new Step\NullStep();
    }

    /**
     * @param bool $hasWpCliStep
     * @param bool $hasRoboStep
     * @param Stepper $stepper
     */
    private function createExecutors($hasWpCliStep, $hasRoboStep, Stepper $stepper)
    {
        if (!$hasWpCliStep && $this->config[Config::WP_CLI_COMMANDS]) {
            $stepper->addStep(new Step\WpCliCommandsStep($this->io));
            $hasWpCliStep = true;
        }

        if (!$hasRoboStep && $this->config[Config::ROBO_FILE]) {
            $stepper->addStep(new Step\RoboStep($this->io));
            $hasRoboStep = true;
        }

        if (!$hasWpCliStep && !$hasRoboStep) {
            return;
        }

        $executorFactory = new PhpCliTool\CommandExecutorFactory(
            $this->paths,
            $this->io,
            $this->config,
            $this->composer
        );

        if ($hasWpCliStep) {
            $wpCliExecutor = $executorFactory->create(new WpCli\Tool($this->config));
            $this->config->appendConfig(Config::WP_CLI_EXECUTOR, $wpCliExecutor);
        }

        if ($hasRoboStep) {
            $roboExecutor = $executorFactory->create(new Robo\Tool($this->config, $this->paths));
            $this->config->appendConfig(Config::ROBO_EXECUTOR, $roboExecutor);
        }
    }

    private function logo()
    {
        if ($this->config[Config::VERBOSITY] < 1) {
            return;
        }

        $m = '<fg=magenta>';
        $y = ' </><fg=yellow>';
        ob_start();
        ?>

        <?= $m ?>__      __ ___ <?= $y ?> ___  _____  _    ___  _____  ___  ___  </>
        <?= $m ?>\ \    / /| _ \<?= $y ?>/ __||_   _|/_\  | _ \|_   _|| __|| _ \ </>
        <?= $m ?> \ \/\/ / |  _/<?= $y ?>\__ \  | | / _ \ |   /  | |  | _| |   / </>
        <?= $m ?>  \_/\_/  |_|  <?= $y ?>|___/  |_|/_/ \_\|_|_\  |_|  |___||_|_\ </>

        <?php
        $logo = ob_get_clean();

        $this->io->write($logo);
    }
}
