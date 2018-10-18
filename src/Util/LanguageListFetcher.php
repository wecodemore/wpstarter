<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

/**
 * Utility that fetches a list of supported locales from wp.org API.
 */
class LanguageListFetcher
{
    /**
     * @var array
     */
    private $languages = [];

    /**
     * @var \WeCodeMore\WpStarter\Util\Io
     */
    private $io;

    /**
     * @var UrlDownloader
     */
    private $urlDownloader;

    /**
     * @param \WeCodeMore\WpStarter\Util\Io $io
     * @param UrlDownloader $urlDownloader
     */
    public function __construct(Io $io, UrlDownloader $urlDownloader)
    {
        $this->io = $io;
        $this->urlDownloader = $urlDownloader;
    }

    /**
     * @param string $version
     * @param bool $useSsl
     * @return array
     */
    public function fetch(string $version, bool $useSsl = true): array
    {
        if ($this->languages) {
            return $this->languages;
        }

        $url = $useSsl ? 'https://' : 'http://';
        $url .= "api.wordpress.org/translations/core/1.0/?version={$version}";
        $result = $this->urlDownloader->fetch($url);

        if (!$result && $useSsl) {
            $this->io->writeComment('Language list failed, trying with disabled SSL...');

            return $this->fetch($version, false);
        }

        if (!$result && substr_count($version, '.') > 2) {
            $verArray = explode('.', $version);
            array_pop($verArray);
            $version = implode('.', $verArray);
            $this->io->writeComment("Language list download failed, trying with version {$version}...");

            return $this->fetch($version);
        }

        if (!$result) {
            $this->io->writeComment('Error on loading language list from wordpress.org');
            return [];
        }

        $all = (array)@json_decode($result, true);
        $this->languages = [];
        if (empty($all['translations']) || !is_array($all['translations'])) {
            $this->io->writeComment('Error on loading language list from wordpress.org');

            return [];
        }

        foreach ($all['translations'] as $lang) {
            $language = $lang['language'] ?? '';
            ($language && is_string($language)) and $this->languages[] = strip_tags($language);
        }

        if (!$this->languages) {
            $this->io->writeComment('Error on loading language list from wordpress.org');
        }

        return $this->languages;
    }
}
