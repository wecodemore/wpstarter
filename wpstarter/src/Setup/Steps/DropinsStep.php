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

use WCM\WPStarter\Setup\IO;
use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\OverwriteHelper;
use WCM\WPStarter\Setup\UrlDownloader;
use ArrayAccess;
use Exception;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class DropinsStep implements StepInterface
{
    private static $dropins = array(
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
    );

    /**
     * @var \WCM\WPStarter\Setup\IO
     */
    private $io;

    /**
     * @var \WCM\WPStarter\Setup\OverwriteHelper
     */
    private $overwrite;

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
     * @param \WCM\WPStarter\Setup\IO              $io
     * @param \WCM\WPStarter\Setup\OverwriteHelper $overwrite
     */
    public function __construct(IO $io, OverwriteHelper $overwrite)
    {
        $this->io = $io;
        $this->overwrite = $overwrite;
    }

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, ArrayAccess $paths)
    {
        $this->config = $config;

        return ! empty($config['dropins']) && ! empty($paths['wp-content']);
    }

    /**
     * @inheritdoc
     */
    public function run(ArrayAccess $paths)
    {
        foreach ($this->config['dropins'] as $name => $url) {
            $this->validName(basename($name))
                ? $this->runStep($name, $url, $paths)
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
     * @param string       $name
     * @param string       $url
     * @param \ArrayAccess $paths
     */
    private function runStep($name, $url, ArrayAccess $paths)
    {
        $step = new DropinStep($name, $url, $this->io, $this->overwrite);
        if ($step->allowed($this->config, $paths)) {
            $step->run($paths);
            $this->addMessage($step->error(), 'error');
            $this->addMessage($step->success(), 'success');
        }
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
        if ($this->config['unknown-dropins'] === true || in_array($name, self::$dropins, true)) {
            return true;
        }
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'php') {
            return $this->config['unknown-dropins'] === "ask" && $this->ask($name, 0);
        }
        $name = substr($name, 0, -4);
        $languages = $this->fetchLanguages();

        return $languages === false
            ? $this->config['unknown-dropins'] === "ask" && $this->ask($name, 1)
            : (
                in_array($name, $languages, true)
                || ($this->config['unknown-dropins'] === "ask" && $this->ask($name, 2))
            );
    }

    /**
     * Fetch languages from wordpress.org API.
     *
     * @param  bool       $ssl
     * @return array|bool
     */
    private function fetchLanguages($ssl = true)
    {
        static $languages;
        if (! is_null($languages)) {
            return $languages;
        }
        $url = $ssl ? 'https' : 'http';
        $url .= '://api.wordpress.org/translations/core/1.0/?version=';
        $remote = new UrlDownloader($url.$this->config['wp-version']);
        $result = $remote->fetch(true);
        if (! $result) {
            return $ssl ? $this->fetchLanguages(false) : false;
        }
        try {
            $all = (array) json_decode($result, true);
            $languages = isset($all['translations']) ? array() : false;
            if (is_array($languages)) {
                foreach ($all['translations'] as $lang) {
                    $languages[] = $lang['language'];
                }
            }
        } catch (Exception $e) {
            $languages = false;
        }
        if ($languages === false) {
            $this->io->comment('Error on loading languages from wordpress.org');
        }

        return $languages;
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
                $lines = array(
                    "{$name} is not a core supported locale for WP ".$this->config['wp-version'],
                    "Do you want to proceed with {$name}.php anyway?",
                );
                break;
            case 1:
                $lines = array(
                    'WP Starter failed to get languages from wordpress.org API,',
                    "so it isn't possible to verify that {$name} is a supported locale.",
                    "Do you want to proceed with {$name}.php anyway?",
                );
                break;
            case 0:
            default:
                $lines = array(
                    "{$name} seems not a valid dropin file.",
                    "Do you want to proceed with it anyway?",
                );
                break;

        }

        return $this->io->ask($lines, false);
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
