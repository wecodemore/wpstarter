<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Step;

use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\LanguageListFetcher;
use WeCodeMore\WpStarter\Util\Locator;
use WeCodeMore\WpStarter\Util\OverwriteHelper;
use WeCodeMore\WpStarter\Util\Paths;

/**
 * Step to process dropins.
 */
final class DropinsStep implements Step
{
    const NAME = 'dropins';

    const QUESTION_NO_DROPIN = 0;
    const QUESTION_LOCALES_ERROR = 1;
    const QUESTION_NO_LOCALE = 2;

    const DROPINS = [
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
     * @var array
     */
    private static $languages;

    /**
     * @var \WeCodeMore\WpStarter\Util\Io
     */
    private $io;

    /**
     * @var LanguageListFetcher
     */
    private $languagesFetcher;

    /**
     * @var \WeCodeMore\WpStarter\Util\UrlDownloader
     */
    private $urlDownloader;

    /**
     * @var OverwriteHelper
     */
    private $overwriteHelper;

    /**
     * @var string[]
     */
    private $dropins;

    /**
     * @var \WeCodeMore\WpStarter\Config\Config
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
     * @param Locator $locator
     */
    public function __construct(Locator $locator)
    {
        $this->io = $locator->io();
        $this->languagesFetcher = $locator->languageListFetcher();
        $this->urlDownloader = $locator->urlDownloader();
        $this->overwriteHelper = $locator->overwriteHelper();
        $this->config = $locator->config();
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
        return $config[Config::DROPINS]->notEmpty() && $paths->wpContent();
    }

    /**
     * @param Config $config
     * @param Paths $paths
     * @return int
     */
    public function run(Config $config, Paths $paths): int
    {
        $this->dropins = $this->config[Config::DROPINS]->unwrapOrFallback([]);

        if (!$this->dropins || !is_array($this->dropins)) {
            return self::NONE;
        }

        foreach ($this->dropins as $name => $url) {
            $this->isKnownDropin(basename($name))
                ? $this->runStep($name, $url, $paths)
                : $this->addMessage("{$name} is not a valid dropin name. Skipped.", 'error');
        }

        if (!$this->error) {
            return self::SUCCESS;
        }

        if (!$this->success) {
            return self::ERROR;
        }

        return self::SUCCESS | self::ERROR;
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return trim($this->error);
    }

    /**
     * @return string
     */
    public function success(): string
    {
        return trim($this->success);
    }

    /**
     * @param string $name
     * @param string $url
     * @param Paths $paths
     */
    private function runStep(string $name, string $url, Paths $paths)
    {
        $step = new DropinStep(
            $name,
            $url,
            $this->io,
            $this->urlDownloader,
            $this->overwriteHelper
        );

        if ($step->allowed($this->config, $paths)) {
            $step->run($this->config, $paths);
            $error = $step->error();
            $success = $step->success();
            $error and $this->addMessage($error, 'error');
            $success and $this->addMessage($success, 'success');
        }
    }

    /**
     * Besides dropins stored in DROPINS class constant, locales files are valid dropins as well.
     * This method checks that required dropin is one of the default or one of supported locales,
     * retrieved from wordpress.org API.
     * Via "unknown-dropins" config is possible to change how this method acts in case of unknown
     * dropins.
     *
     * @param  string $name
     * @return bool
     */
    private function isKnownDropin(string $name): bool
    {
        if ($this->config[Config::UNKWOWN_DROPINS]->is(true)
            || in_array($name, self::DROPINS, true)
        ) {
            return true;
        }

        $shouldAsk = $this->config[Config::UNKWOWN_DROPINS]->is(OptionalStep::ASK);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'php') {
            return $shouldAsk && $this->ask($name, self::QUESTION_NO_DROPIN);
        }

        if (!is_array(self::$languages)) {
            $ver = $this->config[Config::WP_VERSION]->unwrapOrFallback();
            $languages = $ver ? $this->languagesFetcher->fetch($ver) : [];
            is_array($languages) and self::$languages = $languages;
        }

        $name = substr($name, 0, -4);

        if (!self::$languages || !is_array(self::$languages)) {
            return $shouldAsk && $this->ask($name, self::QUESTION_LOCALES_ERROR);
        }

        return
            in_array($name, self::$languages, true)
            || ($shouldAsk && $this->ask($name, self::QUESTION_NO_LOCALE));
    }

    /**
     * Asks to user what to do in case of unknown dropins.
     * Question is different based on situations.
     *
     * @param string $name
     * @param int $question
     * @return bool
     */
    private function ask(string $name, int $question = 0): bool
    {
        $wpVer = $this->config[Config::WP_VERSION]->unwrapOrFallback('');
        $forWp = $wpVer ? " for WP '{$wpVer}'" : '';

        switch ($question) {
            case self::QUESTION_NO_LOCALE:
                $lines = [
                    "{$name} is not a core supported locale{$forWp}.",
                    "Do you want to proceed with {$name}.php anyway?",
                ];
                break;
            case self::QUESTION_LOCALES_ERROR:
                $lines = [
                    'WP Starter failed to get languages from wordpress.org API,',
                    "so it isn't possible to verify that {$name} is a supported locale.",
                    "Do you want to proceed with {$name}.php anyway?",
                ];
                break;
            case self::QUESTION_NO_DROPIN:
            default:
                $lines = [
                    "{$name} seems not a valid dropin file.",
                    'Do you want to proceed with it anyway?',
                ];
                break;
        }

        return $this->io->askConfirm($lines, false);
    }

    /**
     * @param string $message
     * @param string $type
     */
    private function addMessage(string $message, string $type)
    {
        $this->{$type} .= $message ? $message . PHP_EOL : '';
    }
}
