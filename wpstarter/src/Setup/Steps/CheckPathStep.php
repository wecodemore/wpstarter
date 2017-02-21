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
     * {@inheritdoc}
     */
    public function allowed(Config $config, ArrayAccess $paths)
    {
        $this->config = $config;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function run(ArrayAccess $paths)
    {
        $this->paths = $paths;
        $toCheck = array(
            realpath($paths['root'].'/'.$paths['starter']),
            realpath($paths['root'].'/'.$paths['vendor'].'/autoload.php'),
            realpath($paths['root'].'/'.$paths['wp'].'/wp-settings.php'),
        );
        if (array_filter($toCheck) !== $toCheck) {
            $this->error = 'WP Starter was not able to find valid folder settings.';

            return self::ERROR;
        }
        if (
            $paths['wp-content']
            && $paths['wp-parent']
            && strpos(trim($paths['wp-content'], '\\/'), trim($paths['wp-parent'], '\\/')) !== 0
        ) {
            $this->error =
                'Content folder must share parent folder with WP folder, or be contained in it.'
                .' Use the "wordpress-content-dir" setting to properly set it';

            return self::ERROR;
        }
        // no love for this, but https://core.trac.wordpress.org/ticket/31620 makes it necessary
        if ($paths['wp-content'] && $this->config['move-content'] !== true) {
            try {
                $dir = $paths['root'].'/'.$paths['wp-content'].'/themes';
                $this->themeDir = is_dir($dir) || mkdir($dir, 0755, true);
            } catch (Exception $e) {
                $this->themeDir = false;
            }
        }

        return self::SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function success()
    {
        return 'All paths recognized.';
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess(IO $io)
    {
        if (!$this->themeDir) {
            $lines = array(
                'Default theme folder:',
                '"'.$this->paths['wp-content'].'/themes" does not exist.',
                'The site may be unusable until you create it (even empty).',
            );
            $io->block($lines, 'red', true);
        }
    }
}
