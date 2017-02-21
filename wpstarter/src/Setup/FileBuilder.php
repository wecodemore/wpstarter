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

use ArrayAccess;
use Exception;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class FileBuilder
{
    /**
     * @var bool
     */
    private $isRoot;

    /**
     * @param $isRoot
     */
    public function __construct($isRoot)
    {
        $this->isRoot = $isRoot;
    }

    /**
     * Build a file content starting form a template and a set of replacement variables.
     * Part of those variables (salt keys) are generated using Salter class.
     * Templater class is used to apply the replacements.
     *
     * @param \ArrayAccess $paths
     * @param string       $template
     * @param array        $vars
     *
     * @return string|bool file content on success, false on failure
     */
    public function build(ArrayAccess $paths, $template, array $vars = array())
    {
        $pieces = array($paths['starter'], 'templates', $template);
        if (!$this->isRoot) {
            array_unshift($pieces, $paths['root']);
        }
        $template = implode('/', $pieces);
        if (!is_readable($template)) {
            return false;
        }
        /** @var string $content */
        $content = $this->render(file_get_contents($template), $vars);
        if (empty($content)) {
            return false;
        }

        return $content;
    }

    /**
     * Given a file content as string, dump it to a file in a given folder.
     *
     * @param string $content    file content
     * @param string $targetPath target path
     * @param string $fileName   target file name
     *
     * @return bool true on success, false on failure
     */
    public function save($content, $targetPath, $fileName)
    {
        if (empty($content)) {
            return false;
        }
        try {
            return file_put_contents("{$targetPath}/{$fileName}", $content) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $content
     * @param array  $vars
     *
     * @return string
     */
    public function render($content, array $vars)
    {
        foreach ($vars as $key => $value) {
            $content = str_replace('{{{'.$key.'}}}', $value, $content);
        }

        return $content;
    }
}
