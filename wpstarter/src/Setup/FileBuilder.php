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

use Composer\Util\Filesystem;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class FileBuilder
{

    /**
     * @var \Composer\Util\Filesystem
     */
    private $filesystem;

    /**
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * Build a file content starting form a template and a set of replacement variables.
     * Part of those variables (salt keys) are generated using Salter class.
     * Templater class is used to apply the replacements.
     *
     * @param  \ArrayAccess $paths
     * @param  string $template
     * @param  array $vars
     * @return string|bool  file content on success, false on failure
     */
    public function build(\ArrayAccess $paths, $template, array $vars = [])
    {
        $template = realpath($this->filesystem->normalizePath(
            "{$paths['root']}/{$paths['starter']}/templates/{$template}"
        ));

        if (!$template || !is_file($template) || !is_readable($template)) {
            return false;
        }
        /** @var string $content */
        $content = $this->render(file_get_contents($template), $vars);

        return $content ?: false;
    }

    /**
     * @param  string $content
     * @param  array $vars
     * @return string
     */
    public function render($content, array $vars)
    {
        foreach ($vars as $key => $value) {
            $content = str_replace('{{{' . $key . '}}}', $value, $content);
        }

        return $content;
    }
}
