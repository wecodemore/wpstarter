<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup\Steps;

use WCM\WPStarter\Setup\FileBuilder;
use WCM\WPStarter\Setup\Filesystem;
use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\LanguagesFetcher;
use WCM\WPStarter\Setup\OverwriteHelper;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class DropinsStep implements StepInterface
{

    private static $validDropins = [
        'advanced-cache.php',
        'db.php',
        'db-error.php',
        'install.php',
        'maintenance.php',
        'object-cache.php',
        'sunrise.php',
        'blog-deleted.php',
        'blog-inactive.php',
        'blog-suspended.php',
    ];

    /**
     * @var \WCM\WPStarter\Setup\IO
     */
    private $io;

    /**
     * @var array
     */
    private $dropins;

    /**
     * @var \WCM\WPStarter\Setup\Config
     */
    private $config;

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var string
     */
    private $success = '';

    /**
     * @param \WCM\WPStarter\Setup\IO          $io
     * @param \WCM\WPStarter\Setup\Filesystem  $filesystem
     * @param \WCM\WPStarter\Setup\FileBuilder $filebuilder
     * @return static
     */
    public static function instance(
        IO $io,
        Filesystem $filesystem,
        FileBuilder $filebuilder
    ) {
        return new static($io);
    }

    /**
     * @param \WCM\WPStarter\Setup\IO $io
     */
    public function __construct(IO $io)
    {
        $this->io = $io;
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return 'dropins';
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, \ArrayAccess $paths)
    {
        $this->config = $config;

        return ! empty($config['dropins']) && ! empty($paths['wp-content']);
    }

    /**
     * @inheritdoc
     */
    public function run(\ArrayAccess $paths)
    {
        $overwrite = new OverwriteHelper($this->config, $this->io, $paths);
        is_array($this->dropins) or $this->dropins = $this->config['dropins'];

        if (empty($this->dropins)) {
            return self::NONE;
        }

        foreach ($this->dropins as $name => $url) {
            $this->validName(basename($name))
                ? $this->runStep($name, $url, $paths, $overwrite)
                : $this->addMessage("{$name} is not a valid dropin name. Skipped.", 'error');
        }
        if (empty($this->error)) {
            return self::SUCCESS;
        } elseif (empty($this->success)) {
            return self::ERROR;
        }

        return self::SUCCESS | self::ERROR;
    }

    /**
     * @inheritdoc
     */
    public function error()
    {
        return trim($this->error);
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        return trim($this->success);
    }

    /**
     * @param string                               $name
     * @param string                               $url
     * @param \ArrayAccess                         $paths
     * @param \WCM\WPStarter\Setup\OverwriteHelper $overwrite
     */
    private function runStep($name, $url, \ArrayAccess $paths, OverwriteHelper $overwrite)
    {
        $step = new DropinCreationStep($name, $url, $this->io, $overwrite);
        if ($step->allowed($this->config, $paths)) {
            $step->run($paths);
            $this->addMessage($step->error(), 'error');
            $this->addMessage($step->success(), 'success');
        }
    }

    /**
     * Besides dropins stored in class $dropins variable, locale files are valid dropins as well.
     * This method checks that required dropin is one of the default or one of supported locales,
     * retrieved from wordpress.org API.
     * Via "unknown-dropins" config is possible to change how this method acts in case of unknown
     * dropins.
     *
     * @param  string $name
     * @return bool
     */
    private function validName($name)
    {
        if (
            $this->config['unknown-dropins'] === true
            || in_array($name, self::$validDropins, true)
        ) {
            return true;
        }
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $ask = $this->config['unknown-dropins'] === 'ask';
        if (strtolower($ext) !== 'php') {
            return $ask && $this->ask($name, 0);
        }

        $name = substr($name, 0, -4);
        $fetcher = new LanguagesFetcher($this->io);
        $languages = $fetcher->fetch($this->config['wp-version']);
        if (is_array($languages) && $languages) {
            self::$validDropins = array_merge(self::$validDropins, $languages);
        }

        return $languages
            ? in_array($name, $languages, true) || ($ask && $this->ask($name, 2))
            : $ask && $this->ask($name, 1);
    }

    /**
     * Asks to user what to do in case of unknown dropins.
     * Question is different based on situations.
     *
     * @param  string $name
     * @param  int    $question
     * @return bool
     */
    private function ask($name, $question = 0)
    {
        switch ($question) {
            case 2:
                $lines = [
                    "{$name} is not a core supported locale for WP ".$this->config['wp-version'],
                    "Do you want to proceed with {$name}.php anyway?",
                ];
                break;
            case 1:
                $lines = [
                    'WP Starter failed to get languages from wordpress.org API,',
                    "so it isn't possible to verify that {$name} is a supported locale.",
                    "Do you want to proceed with {$name}.php anyway?",
                ];
                break;
            case 0:
            default:
                $lines = [
                    "{$name} seems not a valid dropin file.",
                    "Do you want to proceed with it anyway?",
                ];
                break;

        }

        return $this->io->confirm($lines, false);
    }

    /**
     * @param string $message
     * @param string $type
     */
    private function addMessage($message, $type)
    {
        $this->$type .= $message ? $message.PHP_EOL : '';
    }
}
