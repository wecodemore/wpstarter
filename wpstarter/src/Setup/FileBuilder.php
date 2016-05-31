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
class FileBuilder
{
    /**
     * @var bool
     */
    private $isRoot;

    /**
     * @var \WCM\WPStarter\Setup\Filesystem
     */
    private $filesystem;

    /**
     * @param                                 $isRoot
     * @param \WCM\WPStarter\Setup\Filesystem $filesystem
     */
    public function __construct($isRoot, Filesystem $filesystem = null)
    {
        $this->isRoot = $isRoot;
        $this->filesystem = $filesystem ? : new Filesystem();
    }

    /**
     * Build a file content starting form a template and a set of replacement variables.
     * Part of those variables (salt keys) are generated using Salter class.
     * Templater class is used to apply the replacements.
     *
     * @param  \ArrayAccess $paths
     * @param  string       $template
     * @param  array        $vars
     * @return string|bool  file content on success, false on failure
     */
    public function build(\ArrayAccess $paths, $template, array $vars = [])
    {
        $pieces = [$paths['starter'], 'templates', $template];
        if (! $this->isRoot) {
            array_unshift($pieces, $paths['root']);
        }
        $template = implode('/', $pieces);
        if (! is_readable($template)) {
            return false;
        }
        /** @var string $content */
        $content = $this->render(file_get_contents($template), $vars);

        return $content ? : false;
    }

    /**
     * @param  string $content
     * @param  array  $vars
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
