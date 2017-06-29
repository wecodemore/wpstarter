<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Env;

use Symfony\Component\Dotenv\Dotenv as SymfonyDotenv;
use Symfony\Component\Dotenv\Exception\PathException;


/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package WeCodeMore\WpStarter
 * @license http://opensource.org/licenses/MIT MIT
 */
final class Dotenv
{

    /**
     * @var SymfonyDotenv
     */
    private $dotenv;

    /**
     * @param SymfonyDotenv|null $dotenv
     */
    public function __construct(SymfonyDotenv $dotenv = null)
    {
        $this->dotenv = $dotenv ?: new SymfonyDotenv();
    }

    /**
     * @var string[]
     */
    private $loadedNames;

    /**
     * @param string $path
     * @throws \Symfony\Component\Dotenv\Exception\PathException
     * @throws \Symfony\Component\Dotenv\Exception\FormatException
     */
    public function load($path)
    {
        if (!is_readable($path)) {
            throw new PathException($path);
        }

        $values = $this->dotenv->parse(file_get_contents($path), $path);
        $this->loadedNames = [];

        foreach ($values as $name => $value) {
            in_array($name, $this->loadedNames, true) or $this->loadedNames[] = $name;
            if (!isset($_ENV[$name]) && getenv($name) === false) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }

    /**
     * @return string[]
     */
    public function loadedVarNames()
    {
        return $this->loadedNames;
    }

}