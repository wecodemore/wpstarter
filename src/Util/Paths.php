<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Util;

use Composer\Config;
use Composer\Util\Filesystem;

/**
 * Data storage for all relevant paths in a project.
 *
 * Many paths can be configured, this helpers provide a way to do the configuration parsing only
 * once that use helper methods to obtain relative or absolute paths to specific folders.
 */
final class Paths implements \ArrayAccess
{
    const ROOT = 'root';
    const VENDOR = 'vendor';
    const BIN = 'bin';
    const WP = 'wp';
    const WP_PARENT = 'wp-parent';
    const WP_CONTENT = 'wp-content';
    const WP_STARTER = 'wp-starter';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $extra;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string[]
     */
    private $customTemplatesDir = [];

    /**
     * @var array
     */
    private $paths;

    /**
     * @param Config $config
     * @param array $extra
     * @param Filesystem $filesystem
     */
    public function __construct(Config $config, array $extra, Filesystem $filesystem)
    {
        $this->config = $config;
        $this->extra = $extra;
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $templatesRootDir
     */
    public function useCustomTemplatesDir(string $templatesRootDir)
    {
        if (is_dir($templatesRootDir)) {
            $this->customTemplatesDir[] = rtrim($templatesRootDir, '/');
        }
    }

    /**
     * @param string $pathName Use one of the class constants
     * @param string $to
     * @return string
     */
    public function absolute(string $pathName, string $to = ''): string
    {
        if (!$this->offsetExists($pathName)) {
            throw new \InvalidArgumentException(
                sprintf('%s is not a valid WP Starter path key.', $pathName)
            );
        }

        return $this->filesystem->normalizePath($this->paths[$pathName] . $this->to($to));
    }

    /**
     * @param string $pathName Use one of the class constants
     * @param string $to
     * @return string
     */
    public function relativeToRoot(string $pathName, string $to = ''): string
    {
        if (!$this->offsetExists($pathName)) {
            throw new \InvalidArgumentException(
                sprintf('%s is not a valid WP Starter path key.', $pathName)
            );
        }

        if ($pathName === self::ROOT) {
            return $this->to($to) ?: './';
        }

        $subdir = $this->filesystem->findShortestPath(
            $this->paths[self::ROOT],
            $this->paths[$pathName]
        );

        $to = $this->to($to);
        $to and $subdir = rtrim($subdir, '/\\') . $to;

        return $subdir;
    }

    /**
     * @param string $to
     * @return string
     */
    public function root(string $to = ''): string
    {
        return $this->absolute(self::ROOT, $to);
    }

    /**
     * @param string $to
     * @return string
     */
    public function vendor(string $to = ''): string
    {
        return $this->absolute(self::VENDOR, $to);
    }

    /**
     * @param string $to
     * @return string
     */
    public function bin(string $to = ''): string
    {
        return $this->absolute(self::BIN, $to);
    }

    /**
     * @param string $to
     * @return string
     */
    public function wp(string $to = ''): string
    {
        return $this->absolute(self::WP, $to);
    }

    /**
     * @param string $to
     * @return string
     */
    public function wpParent(string $to = ''): string
    {
        return $this->absolute(self::WP_PARENT, $to);
    }

    /**
     * @param string $to
     * @return string
     */
    public function wpContent(string $to = ''): string
    {
        return $this->absolute(self::WP_CONTENT, $to);
    }

    /**
     * @param string $to
     * @return string
     */
    public function wpStarter(string $to = ''): string
    {
        return $this->absolute(self::WP_STARTER, $to);
    }

    /**
     * @param string $filename
     * @return string
     */
    public function template(string $filename): string
    {
        foreach ($this->customTemplatesDir as $dir) {
            if (is_file("{$dir}/{$filename}")) {
                return "{$dir}/{$filename}";
            }
        }

        return $this->wpStarter("templates/{$filename}");
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (!is_array($this->paths)) {
            $this->paths = $this->parse();
        }

        return array_key_exists($offset, $this->paths);
    }

    /**
     * @param string $offset
     * @return string
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfRangeException(
                sprintf('%s is not a valid WP Starter path index.', $offset)
            );
        }

        return $this->paths[$offset];
    }

    /**
     * @param string $offset
     * @param string $value
     */
    public function offsetSet($offset, $value)
    {
        if ($this->offsetExists($offset)) {
            throw new \BadMethodCallException(
                sprintf(
                    '%s is append-only: can\'t set %s path because that name is already set.',
                    __CLASS__,
                    $offset
                )
            );
        }

        $this->paths[$offset] = $value;
    }

    /**
     * Disabled.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException(sprintf('%s class does not support unset.', __CLASS__));
    }

    /**
     * @return array
     */
    private function parse(): array
    {
        $cwd = $this->filesystem->normalizePath(getcwd());

        $wpInstallDir = $this->extra['wordpress-install-dir'] ?? 'wordpress';
        $wpContentDir = $this->extra['wordpress-content-dir'] ?? 'wp-content';
        $wpFullDir = $this->filesystem->normalizePath("{$cwd}/{$wpInstallDir}");
        $wpContentFullDir = $this->filesystem->normalizePath("{$cwd}/{$wpContentDir}");
        $wpParentDir = $wpFullDir === $cwd ? $wpFullDir : dirname($wpFullDir);

        if (strpos($wpFullDir, $cwd) !== 0) {
            throw new \Exception(
                'Config for WP install dir is pointing a dir outside root, '
                .'WP Starter does not support that.'
            );
        }

        if (strpos($wpContentFullDir, $cwd) !== 0 || $cwd === $wpContentFullDir) {
            $to = $cwd === $wpContentFullDir ? 'root dir' : 'a dir outside root';
            throw new \Exception(
                "Config for WP config dir is pointing to {$to}, WP Starter does not support that."
            );
        }

        if (strpos($wpContentFullDir, $wpParentDir) !== 0) {
            throw new \Exception(
                'WP content folder must share parent folder with WP folder, or be contained in it.'
                . ' Use the "wordpress-content-dir" setting to properly set it'
            );
        }

        return [
            self::ROOT => $this->filesystem->normalizePath($cwd),
            self::VENDOR => $this->filesystem->normalizePath($this->config->get('vendor-dir')),
            self::BIN => $this->filesystem->normalizePath($this->config->get('bin-dir')),
            self::WP => $wpFullDir,
            self::WP_PARENT => $wpParentDir,
            self::WP_CONTENT => $wpContentFullDir,
            self::WP_STARTER => $this->filesystem->normalizePath(dirname(__DIR__, 2)),
        ];
    }

    /**
     * @param string $to
     * @return string
     */
    private function to(string $to): string
    {
        if ($to) {
            $trail = strlen($to) > 1 && in_array(substr($to, -1, 1), ['\\', '/'], true);
            $to = '/' . trim($this->filesystem->normalizePath($to), '/');
            $trail and $to .= '/';
        }

        return $to;
    }
}
