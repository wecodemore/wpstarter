<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class UrlDownloader
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var bool|string
     */
    private $error = false;

    /**
     * Check that software needed to perform the url request is available.
     *
     * @return bool
     */
    public static function checkSoftware()
    {
        return function_exists('curl_version');
    }

    /**
     * Constructor. Validate and store the given url or an error if url is not valid.
     *
     * @param string $url
     */
    public function __construct($url)
    {
        $parse = parse_url($url, PHP_URL_SCHEME);
        if (empty($parse)) {
            $url = 'http://'.ltrim($url, '/');
        }
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $this->url = $url;
        } else {
            $this->error = is_string($url) ? "{$url} is an invalid url." : "Invalid url.";
        }
    }

    /**
     * Download an url and save content to a file.
     *
     * @param  string $filename
     * @return bool
     */
    public function save($filename)
    {
        if (empty($this->url)) {
            return false;
        }
        if (! is_string($filename) || ! is_dir(dirname($filename))) {
            $this->error = "Invalid target path for {$this->url}.";

            return false;
        }
        try {
            $response = $this->fetch();

            return $response && file_put_contents($filename, $response) > 0;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        return false;
    }

    /**
     * Getter for the error.
     *
     * @return bool|string
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * Perform a cUrl request and return the response.
     *
     * @param  bool $json
     * @return bool|string
     */
    public function fetch($json = false)
    {
        if (empty($this->url) || ! self::checkSoftware()) {
            $this->error .= empty($this->url)
                ? ''
                : "WP Starter needs cUrl installed to download files from url.";

            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $headers = $json ? ['Accept: application/json'] : ['Accept: text/plain'];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        $code = (int)$info['http_code'];
        $wanted = $json ? 'application/json' : 'text/plain';
        if (
            ! empty($response)
            && empty($error)
            && $code === 200
            && $this->contentType($info['content_type']) === $wanted
        ) {
            return trim(substr($response, $info['header_size']));
        }
        if (! empty($error)) {
            $this->error = $error;
        } else {
            $this->error = $code !== 200 || empty($response)
                ? "Network error while downloading {$this->url}."
                : "The url {$this->url} did not respond with {$wanted} content type.";
        }

        return false;
    }

    /**
     * Get the mime type from a content type string.
     *
     * @param  string $contentType
     * @return string
     */
    private function contentType($contentType)
    {
        $types = is_string($contentType) ? explode(';', $contentType) : [''];

        return trim(strtolower($types[0]));
    }
}
