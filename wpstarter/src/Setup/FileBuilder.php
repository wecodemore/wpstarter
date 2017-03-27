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
     * Build a file content starting form a template and a set of replacement variables.
     * Part of those variables (salt keys) are generated using Salter class.
     * Templater class is used to apply the replacements.
     *
     * @param  Paths $paths
     * @param  string $template
     * @param  array $vars
     * @return string file content on success, false on failure
     * @throws \InvalidArgumentException
     */
    public function build(Paths $paths, $template, array $vars = [])
    {
        $template = $paths->absolute(Paths::WP_STARTER, "templates/{$template}");

        if (!$template || !is_file($template) || !is_readable($template)) {
            return false;
        }

        return $this->render(file_get_contents($template), $vars);
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
