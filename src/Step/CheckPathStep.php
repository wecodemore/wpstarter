<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Io\Io;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Checks if all the paths WP Starter needs have been recognized properly and exist.
 */
final class CheckPathStep implements BlockingStep, PostProcessStep
{
    public const NAME = 'checkpaths';

    /**
     * @var \WeCodeMore\WpStarter\Util\Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var bool
     */
    private $themeDir = true;

    /**
     * @var string
     */
    private $envInWebRoot = '';

    /**
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->filesystem = $locator->filesystem();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return bool
     */
    public function allowed(Config $config, Paths $paths): bool
    {
        return true;
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        /** @var string $envDir */
        $envDir = $config[Config::ENV_DIR]->unwrapOrFallback() ?: $paths->root();
        if (strpos($envDir, $paths->wpParent()) === 0) {
            $this->envInWebRoot = $envDir;
        }

        $wpContent = $paths->wpContent();

        $this->filesystem->createDir($wpContent);
        // no love for this, but https://core.trac.wordpress.org/ticket/31620 makes it necessary.
        if ($config[Config::MOVE_CONTENT]->not(true) && $paths->wpContent()) {
            $this->themeDir = $this->filesystem->createDir("{$wpContent}/themes");
            // missing plugins' dir isn't as serious as themes' dir, it causes a PHP warning.
            $this->filesystem->createDir("{$wpContent}/plugins");
        }

        $toCheck = [
            'Autoload' => $paths->vendor('/autoload.php'),
            'WordPress' => $paths->wp('/wp-settings.php'),
            'WordPress content' => $wpContent,
        ];

        $error = '';
        foreach ($toCheck as $name => $path) {
            if (!realpath($path)) {
                $error .= "{$name} path '{$path}' not found.\n";
            }
        }

        if ($error) {
            $this->error = trim($error);

            return self::ERROR;
        }

        return self::SUCCESS;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return 'All <comment>paths recognized</comment>.';
    }

    /**
     * @param Io $io
     * @return void
     */
    public function postProcess(Io $io): void
    {
        if ($this->envInWebRoot) {
            $io->writeCommentBlock(
                "The .env file is currently placed in webroot folder: '{$this->envInWebRoot}'.",
                'It is strongly suggested having .env file outside of webroot for security reasons.'
            );
        }

        if (!$this->themeDir) {
            $lines = [
                'Default theme folder does not exist.',
                'The site may be unusable until you create it (even empty).',
            ];

            $io->writeErrorBlock(...$lines);
        }
    }
}
