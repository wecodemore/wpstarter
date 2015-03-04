<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class Renderer
{
    private $replacements = array();

    public function render($template, array $vars)
    {
        $this->replacements = $vars;

        return array_reduce(array_keys($vars), array($this, 'replace'), $template);
    }

    private function replace($carry, $key)
    {
        return str_replace('{{{'.$key.'}}}', $this->replacements[$key], $carry);
    }
}
