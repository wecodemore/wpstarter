<?php
/*
 * This file is part of the wpstarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Env;

use Dotenv\Loader as DotenvLoader;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package wpstarter
 */
final class Loader extends DotenvLoader
{
    private $allVars = array();

    /**
     * Set variable using Dotenv loader and store the name in class var
     *
     * @param string $name
     * @param mixed $value
     */
    public function setEnvironmentVariable($name, $value = null)
    {
        list($name, $value) = $this->normaliseEnvironmentVariable($name, $value);

        in_array($name, $this->allVars, true) or $this->allVars[] = $name;

        if (!$this->immutable || is_null($this->getEnvironmentVariable($name))) {
            parent::setEnvironmentVariable($name, $value);
        }
    }

    /**
     * @return array
     */
    public function allVarNames()
    {
        return $this->allVars;
    }
}
