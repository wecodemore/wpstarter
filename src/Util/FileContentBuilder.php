<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

/**
 * Helper to build files content from a template name and a series of variables that get replaced
 * in the template.
 */
class FileContentBuilder
{
    /**
     * Build a file content starting form a template and a set of replacement variables.
     *
     * @param  Paths $paths
     * @param  string $template
     * @param  array $vars
     * @return string file content on success, false on failure
     */
    public function build(Paths $paths, string $template, array $vars = []): string
    {
        $template = $paths->template($template);

        if (!$template || !is_file($template) || !is_readable($template)) {
            throw new \Exception("Can't build file from template {$template}: file not found.");
        }

        return $this->render(file_get_contents($template), $vars);
    }

    /**
     * @param  string $content
     * @param  array $vars
     * @return string
     */
    public function render(string $content, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $content = str_replace('{{{' . $key . '}}}', $value, $content);
        }

        return $content;
    }
}
