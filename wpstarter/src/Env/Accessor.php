<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Env;

use Gea\Accessor\AccessorInterface;
use Gea\Exception\ReadOnlyWriteException;


/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
final class Accessor implements AccessorInterface
{
    /**
     * @inheritdoc
     */
    public function read($name)
    {
        switch (true) {
            case array_key_exists($name, $_ENV):
                return $_ENV[$name];
            default:
                $value = getenv($name);
                // switch getenv default to null
                $value === false and $value = null;
        }

        return $value;
    }

    /**
     * Disabled because read-only
     *
     * @param  string      $name
     * @param  string|null $value
     * @return void
     */
    public function write($name, $value = null)
    {
        throw ReadOnlyWriteException::forVarName($name, 'write');
    }

    /**
     * Disabled because read-only
     *
     * @param  string $name
     * @return void
     */
    public function discard($name)
    {
        throw ReadOnlyWriteException::forVarName($name, 'discard');
    }
}
