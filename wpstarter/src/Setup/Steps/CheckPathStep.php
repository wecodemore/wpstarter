<?php
/*
 * This file is part of the WPStarter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WCM\WPStarter\Setup\Steps;

use WCM\WPStarter\Setup\Config;
use WCM\WPStarter\Setup\IO;
use ArrayAccess;
use Exception;

/**
 * Steps that check that all paths WP Starter needs have been recognized properly ad exist.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class CheckPathStep implements BlockingStepInterface, PostProcessStepInterface
{
    /**
     * @var string
     */
    private $error = '';

    /**
     * @var \WCM\WPStarter\Setup\Config
     */
    private $config;

    /**
     * @var \ArrayAccess
     */
    private $paths;

    /**
     * @var bool
     */
    private $themeDir = true;

    /**
     * @inheritdoc
     */
    public function allowed(Config $config, ArrayAccess $paths)
    {
        $this->config = $config;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function run(ArrayAccess $paths)
    {
        $this->paths = $paths;
        $fullPath = rtrim($paths['root'].'/'.$paths['site-dir'], '/').'/';
        $toCheck = array(
            realpath($fullPath.$paths['vendor']),
            realpath($fullPath.$paths['wp']),
            realpath($paths['root'].'/'.$paths['starter']),
            realpath($fullPath.$paths['wp'].'/wp-settings.php'),
        );
        if (array_filter($toCheck) !== $toCheck) {
            $this->error = 'WP Starter was not able to find a valid folder settings.';

            return self::ERROR;
        }
        // no love for this, but https://core.trac.wordpress.org/ticket/31620 makes it necessary
        if ($paths['wp-content'] && $this->config['move-content'] !== true) {
            try {
                $dir = $fullPath.$paths['wp-content'].'/themes';
                $this->themeDir = is_dir($dir) || mkdir($dir, 0755, true);
            } catch (Exception $e) {
                $this->themeDir = false;
            }
        }

        return self::SUCCESS;
    }

    /**
     * @inheritdoc
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function success()
    {
        return 'All paths recognized.';
    }

    /**
     * @inheritdoc
     */
    public function postProcess(IO $io)
    {
        if (! $this->themeDir) {
            $lines = array(
                'Default theme folder:',
                '"'.$this->paths['wp-content'].'/themes" does not exist.',
                'The site may be unusable until you create it (even empty).',
            );
            $io->block($lines, 'red', true);
        }
    }
}
