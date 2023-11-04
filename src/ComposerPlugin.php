<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Script\Event;
use Composer\Util\Filesystem;

/**
 * Composer plugin class to run all the WP Starter steps on Composer install or update and also adds
 * 'wpstarter' command to allow doing same thing "on demand".
 *
 * @psalm-suppress MissingConstructor
 * phpcs:disable Inpsyde.CodeQuality.NoAccessors
 */
final class ComposerPlugin implements
    PluginInterface,
    EventSubscriberInterface,
    Capable,
    CommandProvider
{
    public const EXTRA_KEY = 'wpstarter';
    public const EXTENSIONS_TYPE = 'wpstarter-extension';

    public const MODE_NONE = 0;
    public const MODE_COMMAND = 1;
    public const MODE_COMPOSER_INSTALL = 4;
    public const MODE_COMPOSER_UPDATE = 8;

    /**
     * @var bool
     */
    private static $autoload = false;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Util\Locator
     */
    private $locator;

    /**
     * @var int
     */
    private $mode = self::MODE_NONE;

    /**
     * @var PackageInterface[]
     */
    private $updatedPackages = [];

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'post-install-cmd' => 'onAutorunBecauseInstall',
            'post-update-cmd' => 'onAutorunBecauseUpdate',
            'pre-package-update' => 'onPrePackageOperation',
            'pre-package-install' => 'onPrePackageOperation',
        ];
    }

    /**
     * @return array
     */
    public static function defaultSteps(): array
    {
        return [
            Step\CheckPathStep::NAME => Step\CheckPathStep::class,
            Step\WpConfigStep::NAME => Step\WpConfigStep::class,
            Step\IndexStep::NAME => Step\IndexStep::class,
            Step\FlushEnvCacheStep::NAME => Step\FlushEnvCacheStep::class,
            Step\MuLoaderStep::NAME => Step\MuLoaderStep::class,
            Step\EnvExampleStep::NAME => Step\EnvExampleStep::class,
            Step\DropinsStep::NAME => Step\DropinsStep::class,
            Step\MoveContentStep::NAME => Step\MoveContentStep::class,
            Step\ContentDevStep::NAME => Step\ContentDevStep::class,
            Step\WpCliConfigStep::NAME => Step\WpCliConfigStep::class,
            Step\WpCliCommandsStep::NAME => Step\WpCliCommandsStep::class,
            Step\VcsIgnoreCheckStep::NAME => Step\VcsIgnoreCheckStep::class,
        ];
    }

    /**
     * @return array<string, class-string<Capable>>
     */
    public function getCapabilities(): array
    {
        return [CommandProvider::class => __CLASS__];
    }

    /**
     * @return array{WpStarterCommand}
     */
    public function getCommands(): array
    {
        return [new WpStarterCommand()];
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * WP Starter is required from Composer, which means it is deployed with WordPress, and so
     * any autoloading setting that WP Starter declares will "pollute" the Composer autoloader that
     * is loaded at every WordPress request.
     * For this reason we keep in the autoload section of composer.json only the needed bare-minimum
     * then we register a simple PSR-4 loader for the rest.
     *
     * @return void
     */
    public function setupAutoload(): void
    {
        static::$autoload or spl_autoload_register(
            $this->psr4LoaderFor(__NAMESPACE__, __DIR__),
            true,
            true
        );

        static::$autoload = true;
    }

    /**
     * @param PackageEvent $event
     * @return void
     */
    public function onPrePackageOperation(PackageEvent $event): void
    {
        $operation = $event->getOperation();

        $package = null;
        if ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } elseif ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        }

        if ($package && ($package->getType() !== 'composer-plugin')) {
            $this->updatedPackages[] = $package;
        }
    }

    /**
     * @param Event $event
     * @return void
     */
    public function onAutorunBecauseInstall(Event $event): void
    {
        $this->mode or $this->mode = self::MODE_COMPOSER_INSTALL;
        $this->setupAutoload();
        if ($this->composer->getPackage()->getType() === self::EXTENSIONS_TYPE) {
            return;
        }

        $this->run(Util\SelectedStepsFactory::autorun());
    }

    /**
     * @param Event $event
     * @return void
     */
    public function onAutorunBecauseUpdate(Event $event): void
    {
        $this->mode = self::MODE_COMPOSER_UPDATE;
        $this->onAutorunBecauseInstall($event);
    }

    /**
     * @param Util\SelectedStepsFactory $factory
     * @return void
     *
     * phpcs:disable Inpsyde.CodeQuality.FunctionLength
     */
    public function run(Util\SelectedStepsFactory $factory): void
    {
        // phpcs:enable Inpsyde.CodeQuality.FunctionLength
        $this->mode or $this->mode = self::MODE_COMMAND;

        /*
         * Why two try/catch blocks: the 2nd `catch` relies on the Locator, that is built inside
         * `prepareRun()` which means we can't call `prepareRun()` in the same `try`.
         */

        try {
            $config = $this->prepareRun($factory);
        } catch (\Throwable $throwable) {
            $print = function (string $line): void {
                $this->io->writeError(sprintf('  <fg=red>%s</>', trim($line)));
            };

            $this->io->write('');
            $print($throwable->getMessage());
            if ($this->io->isVerbose()) {
                $this->io->write('');
                array_map($print, explode("\n", $throwable->getTraceAsString()));
            }

            if ($this->mode === self::MODE_COMMAND) {
                exit(1);
            }

            return;
        }

        $this->convertErrorsToExceptions();
        $exit = 0;

        try {
            $this->loadExtensions();
            $isFullRun = $factory->isFullRun();

            $isFullRun and $this->checkWp($config);

            $isFullRun and $this->logo();
            $this->compatibilityMode($config);

            if ($factory->isListMode()) {
                $factory->selectAndFactory($this->locator, $this->composer);

                return;
            }

            $isFullRun and $this->checkDb($config);

            $runner = $factory->isSelectedCommandMode()
                ? Step\Steps::commandMode($this->locator, $this->composer)
                : Step\Steps::composerMode($this->locator, $this->composer);

            $runner
                ->addStep(...$this->factoryStepsToRun($factory))
                ->run($this->locator->config(), $this->locator->paths());
        } catch (\Throwable $throwable) {
            $exit = 1;

            $lines = [$throwable->getMessage()];
            if ($this->io->isVerbose()) {
                $lines = explode("\n", $throwable->getTraceAsString());
                array_unshift($lines, '');
                array_unshift($lines, $throwable->getMessage());
            }

            $this->locator->io()->writeErrorBlock(...$lines);
        } finally {
            restore_error_handler();
            if ($this->mode === self::MODE_COMMAND) {
                exit($exit);
            }
        }
    }

    /**
     * @param Util\SelectedStepsFactory $factory
     * @return Config\Config
     * @throws \Exception
     */
    private function prepareRun(Util\SelectedStepsFactory $factory): Config\Config
    {
        switch (true) {
            case ($this->mode === self::MODE_COMPOSER_INSTALL):
                $requirements = Util\Requirements::forComposerInstall(
                    $this->composer,
                    $this->io,
                    new Filesystem(),
                    ...$this->updatedPackages
                );
                break;
            case ($this->mode === self::MODE_COMPOSER_UPDATE):
                $requirements = Util\Requirements::forComposerUpdate(
                    $this->composer,
                    $this->io,
                    new Filesystem(),
                    ...$this->updatedPackages
                );
                break;
            case ($factory->isSelectedCommandMode()):
                $requirements = Util\Requirements::forSelectedStepsCommand(
                    $this->composer,
                    $this->io,
                    new Filesystem()
                );
                break;
            default:
                $requirements = Util\Requirements::forGenericCommand(
                    $this->composer,
                    $this->io,
                    new Filesystem()
                );
                break;
        }

        $config = $requirements->config();

        /** @var string|null $autoload */
        $autoload = $config[Config\Config::AUTOLOAD]->unwrapOrFallback();
        if ($autoload && is_file($autoload)) {
            require_once $autoload;
        }

        $this->locator = new Util\Locator($requirements, $this->composer, $this->io);

        return $config;
    }

    /**
     * @return void
     */
    private function convertErrorsToExceptions(): void
    {
        set_error_handler(
            static function (int $code, string $msg, string $file = '', int $line = 0): void {
                if ($file && $line) {
                    $msg = rtrim($msg, '. ') . ", in {$file} line {$line}.";
                }

                throw new \Exception($msg, $code);
            },
            E_ALL
        );
    }

    /**
     * @return void
     */
    private function loadExtensions(): void
    {
        $packages = $this->locator->packageFinder()->findByType(self::EXTENSIONS_TYPE);

        foreach ($packages as $package) {
            $this->loadExtensionAutoload($package);
        }
    }

    /**
     * @param PackageInterface $package
     * @return void
     */
    private function loadExtensionAutoload(PackageInterface $package): void
    {
        $autoload = $package->getExtra()['wpstarter-autoload'] ?? null;
        if (!$autoload || !is_array($autoload)) {
            return;
        }

        $files = $autoload['files'] ?? [];
        $psr4 = $autoload['psr-4'] ?? [];
        is_array($files) or $files = [];
        is_array($psr4) or $psr4 = [];

        if (!$files && !$psr4) {
            return;
        }

        $packagePath = $this->locator->packageFinder()->findPathOf($package);
        $filesystem = $this->locator->composerFilesystem();

        foreach ($psr4 as $namespace => $dir) {
            if (!is_string($namespace) || !is_string($dir)) {
                continue;
            }
            $fullpath = $filesystem->normalizePath("{$packagePath}/{$dir}");
            is_dir($fullpath) and spl_autoload_register(
                $this->psr4LoaderFor(rtrim($namespace, '\\/'), $fullpath),
                true,
                true
            );
        }

        foreach ($files as $file) {
            if (is_string($file)) {
                $fullpath = $filesystem->normalizePath("{$packagePath}/{$file}");
                file_exists($fullpath) and require_once $fullpath;
            }
        }
    }

    /**
     * @param Config\Config $config
     * @return void
     */
    private function checkWp(Config\Config $config): void
    {
        $requireWp = $config[Config\Config::REQUIRE_WP]->not(false);
        /** @var string $fallbackVer */
        $fallbackVer = $config[Config\Config::WP_VERSION]->unwrapOrFallback('');
        $wpVersion = '';
        if ($requireWp) {
            $wpVersionDiscover = new Util\WpVersion(
                $this->locator->packageFinder(),
                $this->locator->io(),
                $fallbackVer
            );
            $wpVersion = $wpVersionDiscover->discover();
        }

        if (!$wpVersion && $requireWp) {
            throw new \RuntimeException('WordPress is required but not found.');
        }

        // If WP version found and no version is in configs, let's set it with the finding.
        if ($wpVersion && !$fallbackVer) {
            $config[Config\Config::WP_VERSION] = $fallbackVer;
        }
    }

    /**
     * @param Util\SelectedStepsFactory $factory
     * @return Step\Step[]
     */
    private function factoryStepsToRun(Util\SelectedStepsFactory $factory): array
    {
        $steps = $factory->selectAndFactory($this->locator, $this->composer);
        if (!$steps) {
            throw new \Exception($factory->lastFatalError() ?: 'Nothing to run.');
        }

        $error = $factory->lastError();
        if ($error) {
            $io = $this->locator->io();
            $io->writeError("\n{$error}\n");
        }

        return $steps;
    }

    /**
     * @param string $namespace
     * @param string $dir
     * @return callable(string): void
     */
    private function psr4LoaderFor(string $namespace, string $dir): callable
    {
        return static function (string $class) use ($namespace, $dir): void {
            if (stripos($class, $namespace) === 0) {
                /** @psalm-ignore-falsable-return */
                $file = substr(str_replace('\\', '/', $class), strlen($namespace));
                require_once $dir . "{$file}.php";
            }
        };
    }

    /**
     * @return void
     */
    private function logo(): void
    {
        // phpcs:disable
        $logo = <<<LOGO
<fg=magenta>    __      __ ___  </><fg=yellow>   ___  _____  _    ___  _____  ___  ___  </>
<fg=magenta>    \ \    / /| _ \ </><fg=yellow>  / __||_   _|/_\  | _ \|_   _|| __|| _ \ </>
<fg=magenta>     \ \/\/ / |  _/ </><fg=yellow>  \__ \  | | / _ \ |   /  | |  | _| |   / </>
<fg=magenta>      \_/\_/  |_|   </><fg=yellow>  |___/  |_|/_/ \_\|_|_\  |_|  |___||_|_\ </>
LOGO;
        // phpcs:enable

        $this->io->write("\n{$logo}\n");
    }

    /**
     * @param Config\Config $config
     * @return void
     */
    private function checkDb(Config\Config $config): void
    {
        $check = $config[Config\Config::DB_CHECK];
        $skipped = $config[Config\Config::SKIP_DB_CHECK]->not(false);
        if ($skipped) {
            $this->io->write(
                sprintf(
                    'The configuration "%s" is deprecated, please use "%s" instead.',
                    Config\Config::SKIP_DB_CHECK,
                    Config\Config::DB_CHECK
                )
            );
        }
        if ($check->not(false) && !$skipped) {
            $check->is(Util\DbChecker::HEALTH_CHECK)
                ? $this->locator->dbChecker()->mysqlcheck()
                : $this->locator->dbChecker()->check();
        }
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // noop
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // noop
    }

    /**
     * @param Config\Config $config
     * @return void
     */
    private function compatibilityMode(Config\Config $config): void
    {
        $paths = $this->locator->paths();
        $root = $paths->root();
        if ($config[Config\Config::WP_CONFIG_PATH]->is($root)) {
            return;
        }

        $wpParent = $paths->wpParent();

        $this->io->isVerbose() and $this->locator->io()->writeCommentBlock(
            'WP Starter will write wp-config.php in',
            '"compatibility mode".',
            "That is, it will be written inside '{$wpParent}' where it was located "
            . 'before updating WP Starter.',
            "If starting a project from scratch, it would be written in root folder '{$root}'."
        );
    }
}
