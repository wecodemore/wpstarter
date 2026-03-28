<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Util;

use Composer\Config;
use Composer\Util\Filesystem;

/**
 * Data storage for all relevant paths in a project.
 *
 * Many paths can be configured, this helper provides a way to do the configuration parsing only
 * once that use helper methods to obtain relative or absolute paths to specific folders.
 *
 * @phpstan-implements \ArrayAccess<string, string>
 */
final class Paths implements \ArrayAccess
{
    public const ROOT = 'root';
    public const VENDOR = 'vendor';
    public const BIN = 'bin';
    public const WP = 'wp';
    public const WP_PARENT = 'wp-parent';
    public const WP_CONTENT = 'wp-content';
    public const WP_STARTER = 'wp-starter';

    private Config $config;
    /** @var array<string, mixed> */
    private array $extra;
    private Filesystem $filesystem;
    /** @var list<string> */
    private array $customTemplatesDir = [];
    /** @var non-falsy-string|null */
    private ?string $customRoot = null;
    /** @var array<string,string>|null */
    private ?array $paths = null;

    /**
     * @param string $root
     * @param Config $config
     * @param array<string, mixed> $extra
     * @param Filesystem $filesystem
     * @return Paths
     */
    public static function withRoot(
        string $root,
        Config $config,
        array $extra,
        Filesystem $filesystem
    ): Paths {

        if (!is_dir($root)) {
            throw new \InvalidArgumentException('Cant instantiate Paths object from invalid root.');
        }

        $instance = new static($config, $extra, $filesystem);
        /** @var non-falsy-string $root */
        $instance->customRoot = $root;

        return $instance;
    }

    /**
     * @param Config $config
     * @param array<string, mixed> $extra
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
     * @return void
     */
    public function useCustomTemplatesDir(string $templatesRootDir): void
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

        return $this->to($this->paths[$pathName], $to); // @phpstan-ignore offsetAccess.notFound
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
            return $this->filesystem->normalizePath($to);
        }

        $subdir = $this->filesystem->findShortestPath(
            $this->paths[self::ROOT], // @phpstan-ignore offsetAccess.notFound
            $this->paths[$pathName] // @phpstan-ignore offsetAccess.notFound
        );

        $to = $this->to('', $to);
        ($to !== '') and $subdir = rtrim($subdir, '/\\') . $to;

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
     * @param mixed $offset
     * @return bool
     *
     * @phpstan-assert array<string, string> $this->paths
     */
    public function offsetExists($offset): bool
    {
        if (!is_array($this->paths)) {
            $this->paths = $this->parse();
        }

        if (!is_string($offset)) {
            throw new \InvalidArgumentException(
                sprintf('%s offset must be a string, %s given.', __CLASS__, gettype($offset))
            );
        }

        return array_key_exists($offset, $this->paths);
    }

    /**
     * @param string $offset
     * @return string
     */
    public function offsetGet($offset): string
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfRangeException(
                sprintf('%s is not a valid WP Starter path index.', $offset)
            );
        }

        return $this->paths[$offset]; // @phpstan-ignore offsetAccess.notFound
    }

    /**
     * @param string|null $offset
     * @param string $value
     */
    public function offsetSet($offset, $value): void
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
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException(sprintf('%s class does not support unset.', __CLASS__));
    }

    /**
     * @return array<string, string>
     */
    private function parse(): array
    {
        $vendorDir = $this->config->get('vendor-dir');
        $binDir = $this->config->get('bin-dir');
        assert(is_string($vendorDir));
        assert(is_string($binDir));
        $cwd = $this->cwd($vendorDir);

        $wpInstallDir = $this->extra['wordpress-install-dir'] ?? 'wordpress';
        $wpContentDir = $this->extra['wordpress-content-dir'] ?? 'wp-content';
        assert(is_string($wpInstallDir));
        assert(is_string($wpContentDir));

        $wpFullDir = $this->filesystem->normalizePath("{$cwd}/{$wpInstallDir}");
        $wpContentFullDir = $this->filesystem->normalizePath("{$cwd}/{$wpContentDir}");
        $wpParentDir = ($wpFullDir === $cwd) ? $wpFullDir : dirname($wpFullDir);

        if (strpos($wpFullDir, $cwd) !== 0) {
            throw new \Exception(
                'Config for WP install dir is pointing a dir outside root, '
                . 'WP Starter does not support that.'
            );
        }

        if ((strpos($wpContentFullDir, $cwd) !== 0) || ($cwd === $wpContentFullDir)) {
            $to = ($cwd === $wpContentFullDir) ? 'root dir' : 'a dir outside root';
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
            self::VENDOR => $this->filesystem->normalizePath($vendorDir),
            self::BIN => $this->filesystem->normalizePath($binDir),
            self::WP => $wpFullDir,
            self::WP_PARENT => $wpParentDir,
            self::WP_CONTENT => $wpContentFullDir,
            self::WP_STARTER => $this->filesystem->normalizePath(dirname(__DIR__, 2)),
        ];
    }

    /**
     * @param string $base
     * @param string $to
     * @return string
     */
    private function to(string $base, string $to): string
    {
        $path = $base;
        if ($to !== '') {
            $trail = in_array(substr($to, -1, 1), ['\\', '/'], true);
            $to = '/' . trim($this->filesystem->normalizePath($to), '/');
            $path = $this->filesystem->normalizePath($base . $to);
            $trail and $path .= '/';
        }

        return $path;
    }

    /**
     * @param string $vendorDir
     * @return string
     */
    private function cwd(string $vendorDir): string
    {
        $cwd = $this->customRoot ?? getcwd();
        if ($cwd === false) {
            // If the directory containing this file is inside vendor, WP Starter is required as a
            // dependency, otherwise is the root. In the first case the CWD can be determined as
            // the dir containing the vendor, in the second case as the root of the package.
            $cwd = strpos($this->filesystem->normalizePath(__DIR__), $vendorDir) === 0
                ? dirname($vendorDir)
                : dirname(__DIR__, 2);
        }

        return $this->filesystem->normalizePath($cwd);
    }
}
