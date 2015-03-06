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

use ArrayAccess;
use Exception;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WPStarter
 */
class Builder
{
    /**
     * @var \WCM\WPStarter\IO
     */
    private $io;

    /**
     * @var int
     */
    private $errors = 0;

    /**
     * @var \WCM\WPStarter\Renderer
     */
    private $renderer;

    /**
     * @var \WCM\WPStarter\Salter
     */
    private $salter;

    /**
     * @var bool
     */
    private $isRoot;

    /**
     * @var array $vars
     */
    private $vars;

    /**
     * @var bool|int
     */
    private $gitignoreDone = false;

    /**
     * @param \WCM\WPStarter\IO       $io
     * @param bool                    $isRoot
     * @param \WCM\WPStarter\Renderer $renderer
     * @param \WCM\WPStarter\Salter   $salter
     */
    public function __construct(
        IO $io,
        $isRoot = false,
        Renderer $renderer = null,
        Salter $salter = null
    ) {
        $this->io = $io;
        $this->renderer = $renderer ?: new Renderer();
        $this->salter = $salter ?: new Salter();
        $this->isRoot = $isRoot;
    }

    /**
     * Builds wp-config.php and copy it to root folder.
     * Also copies additional file if in installation mode.
     *
     * @param  \ArrayAccess $paths
     * @return bool
     */
    public function build(ArrayAccess $paths)
    {
        $this->progress('start');
        if (! $this->checkPaths($paths)) {
            return $this->error('error');
        }
        $this->progress('paths_checked');
        $this->moveContent($paths) and $this->progress('move_done');
        $this->buildGitIgnore($paths);
        $this->buildIndex($paths);
        $this->buildWpConfig($paths);
        $this->copy($paths, '.env.example') and $this->progress('env_done');
        $this->gitignoreWarning();
        $this->envWarning($paths['root']);

        return $this->finalMessage();
    }

    /**
     * Check paths.
     *
     * @param  \ArrayAccess $paths
     * @return bool         true on success, false on failure
     */
    private function checkPaths(ArrayAccess $paths)
    {
        $keys = array('vendor', 'wp');
        if (! $this->isRoot) {
            $keys[] = 'starter';
        }
        foreach ($keys as $key) {
            $path = isset($paths[$key]) ?
                realpath($paths['root'].DIRECTORY_SEPARATOR.$paths[$key])
                : false;
            if (! $path) {
                return $this->error('bad_path', $key);
            } elseif ($key === 'wp' && ! file_exists($path.DIRECTORY_SEPARATOR.'wp-settings.php')) {
                return $this->error('bad_wp');
            }
        }
        $this->vars = array(
            'VENDOR_PATH'     => $paths['vendor'],
            'WP_INSTALL_PATH' => $paths['wp'],
            'WP_CONTENT_PATH' => $paths['content'],
        );

        return true;
    }

    /**
     * Move wp-content contents from WP package to root subfolder.
     *
     * @param  \ArrayAccess $paths
     * @return bool         true on success, false on failure
     */
    private function moveContent(ArrayAccess $paths)
    {
        if (empty($paths['content'])) {
            return false;
        }
        $from = str_replace(array('/', '\\'), '/', $paths['wp'].'/wp-content');
        $to = str_replace(array('/', '\\'), '/', $paths['content']);
        $full = str_replace(array('/', '\\'), '/', $paths['root']).'/'.$to;
        if ($from === $to) {
            return ! $this->progress('same_content');
        }
        if (! is_dir($to) && ! mkdir($to, 0755)) {
            return $this->error('create', $to);
        }
        $lines = array(
            'Do you want to move default plugins and themes from',
            'WordPress package wp-content dir to content folder:',
            '"'.$full.'"',
        );
        if (! $this->io->ask($lines, true)) {
            return ! $this->progress('skip_content');
        }

        return $this->moveItems(glob($from.'/*'), $from, $to);
    }

    /**
     * Builds .gitignore if not already present there
     *
     * @param \ArrayAccess $paths
     */
    private function buildGitIgnore(ArrayAccess $paths)
    {
        $root = $paths['root'];
        if (! $this->isRoot && is_file($root.DIRECTORY_SEPARATOR.'.gitignore')) {
            $this->gitignoreDone = 0;

            return;
        }
        $lines = array(
            'Do you want to create a .gitignore file that makes Git ignore',
            ' - common irrelevant files',
            ' - files that contain sensible data (wp-config.php, .env)',
            ' - WordPress package folder',
            ' - wp-content folder',
        );
        if (! $this->io->ask($lines, true)) {
            $this->progress('gitignore_skipped');

            return;
        }
        $wp = $this->gitignorePath($paths['wp'], $root);
        $vendor = $this->gitignorePath($paths['vendor'], $root);
        $this->vars['WP_PATH_IGNORE'] = $wp;
        $this->vars['VENDOR_PATH_IGNORE'] = $vendor;
        $gitignore = $this->buildFile($paths, '.gitignore');
        $content = $paths['content'] ? $this->gitignorePath($paths['content'], $root) : false;
        $already = $content && strpos($wp, $content) !== 0 && strpos($vendor, $content) !== 0;
        $gitignore .= $already ? PHP_EOL.$content : '';
        if ($this->saveFile($gitignore, $root, '.gitignore')) {
            $this->gitignoreDone = true;
            $this->progress('file_done', '.gitignore');
        }
    }

    /**
     * Builds index.php
     *
     * @param \ArrayAccess $paths
     */
    private function buildIndex(ArrayAccess $paths)
    {
        $build = $this->buildFile($paths, 'index.php');
        if ($this->saveFile($build, $paths['root'], 'index.php')) {
            $this->progress('file_done', 'index.php');
        }
    }

    /**
     * Builds wp-config.php
     *
     * @param \ArrayAccess $paths
     */
    private function buildWpConfig(ArrayAccess $paths)
    {
        $build = $this->buildFile($paths, 'wp-config.php');
        if ($this->saveFile($build, $paths['root'], 'wp-config.php')) {
            $this->progress('file_done', 'wp-config.php');
        }
    }

    /**
     * Copy a file from a source path to a root folder.
     *
     * @param  \ArrayAccess $paths
     * @param  string|array $files
     * @param  string       $base
     * @return bool         true on success, false on failure
     */
    private function copy(ArrayAccess $paths, $files, $base = 'starter')
    {
        $pieces = $base === 'starter' ? array($paths[$base], 'templates') : array($paths[$base]);
        if (! $this->isRoot) {
            array_unshift($pieces, $paths['root']);
        }
        $done = true;
        foreach ((array) $files as $file) {
            $source = implode(DIRECTORY_SEPARATOR, array_merge($pieces, array($file)));
            $dest = $paths['root'].DIRECTORY_SEPARATOR.$file;
            try {
                if (! copy($source, $dest)) {
                    $done = $this->error('copy', $source, $dest);
                }
            } catch (Exception $e) {
                $done = $this->error('copy', $source, $dest);
                break;
            }
        }

        return $done;
    }

    /**
     * Move an array of items from a source to a destination folder, only if not already there.
     * If item is a folder and it's already present, restart recursively, but only once.
     * Because the top level source folder is the original 'wp-content', it means we attempt to move
     * singular theme/plugin folders, but not theme/plugin subfolders.
     *
     * @param  array  $items
     * @param  string $from
     * @param  string $to
     * @param  int    $deep
     * @return bool   true on success, false on failure
     */
    private function moveItems(array $items, $from, $to, $deep = 0)
    {
        $ok = true;
        while (! empty($items) && ! empty($ok)) {
            $item = array_shift($items);
            $ok = $this->moveItem($item, $from, $to, $deep);
            if ($ok && count(scandir(dirname($item))) === 2) {
                // after the move, old containing directory is empty, we can delete it
                @rmdir(dirname($item));
            }
        }

        return $ok;
    }

    /**
     * Move an item from a source to a destination folder, only if not already present there.
     * If item is a folder and it's already present, restart recursively trying to move items in
     * it, but only one level deep.
     *
     * @param  string $path relative path of the item to move
     * @param  string $from absolute path of the current containing folder
     * @param  string $to   absolute path of the target containing folder
     * @param  int    $deep
     * @return bool   true on success, false on failure
     */
    private function moveItem($path, $from, $to, $deep = 0)
    {
        $dest = str_replace($from, $to, $path);
        $errors = 0;
        try {
            if (! is_dir($dest) && ! is_file($dest) && ! rename($path, $dest)) {
                $errors++;
                $this->error($path, $dest);
            } elseif (is_dir($dest) && $deep < 1) {
                return $this->moveItems(glob($path.'/*'), $from, $to, $deep + 1);
            }
        } catch (Exception $e) {
            $errors++;
            $this->error('move', $path, $dest);
        }

        return $errors === 0 ? true : $errors;
    }

    /**
     * Build a file content starting form a template and a set of replacement variables.
     * Part of those variables (salt keys) are generated using Salter class.
     * Templater class is used to apply the replacements.
     *
     * @param  \ArrayAccess $paths
     * @param  string       $fileName
     * @return string|bool  file content on success, false on failure
     */
    private function buildFile(ArrayAccess $paths, $fileName)
    {
        $paths = array($paths['starter'], 'templates', $fileName);
        if (! $this->isRoot) {
            array_unshift($paths, $paths['root']);
        }
        $template = implode(DIRECTORY_SEPARATOR, $paths);
        if (! is_readable($template)) {
            return $this->error('create', $fileName);
        }
        /** @var string $content */
        $content = $this->renderer->render(
            file_get_contents($template),
            array_merge($this->salter->keys(), $this->vars)
        );
        if (empty($content)) {
            return $this->error('create', $fileName);
        }

        return $content;
    }

    /**
     * Given a file content as string, dump it to a file in a given folder.
     *
     * @param  string $content    file content
     * @param  string $targetPath target path
     * @param  string $fileName   target file name
     * @return bool   true on success, false on failure
     */
    private function saveFile($content, $targetPath, $fileName)
    {
        if (empty($content)) {
            return false;
        }
        $dest = $targetPath.DIRECTORY_SEPARATOR.$fileName;
        try {
            if (file_put_contents($dest, $content) === false) {
                return $this->error('save', $fileName, 'root folder');
            }
        } catch (Exception $e) {
            return $this->error('save', $fileName, 'root folder');
        }

        return true;
    }

    /**
     * Print progress messages to console.
     *
     * @return bool always true
     */
    private function progress()
    {
        $comments = array(
            'start'             => 'WP Starter is going to start installation...',
            'gitignore_skipped' => ' - .gitignore creation skipped.',
            'skip_content'      => ' - WordPress content directory move skipped.',
            'same_content'      => 'Your content folder is WP content folder.',
        );
        $ok = array(
            'paths_checked' => 'all paths recognized properly',
            'file_done'     => '<comment>%s</comment> generated and saved.',
            'env_done'      => '<comment>.env.example</comment> copied in project root folder.',
            'move_done'     => 'WordPress content directory moved.',
        );
        $args = func_get_args();
        $msg = array_shift($args);
        if (array_key_exists($msg, $comments)) {
            return $this->io->comment($comments[$msg]);
        }

        return $this->io->ok(vsprintf($ok[$msg], $args));
    }

    /**
     * Print error messages to console.
     *
     * @return bool always false
     */
    private function error()
    {
        $errors = array(
            'bad_path' => 'WP Starter was not able to find a valid path for %s folder.',
            'bad_wp'   => 'WP Starter was not able to find a valid WordPress folder.',
            'create'   => 'WP Starter was not able to create %s.',
            'copy'     => 'WP Starter was not able to copy %s to %s.',
            'save'     => 'WP Starter was not able to save %s in %s.',
            'move'     => 'WP Starter was not able to move %s to %s.',
        );
        $args = func_get_args();
        $error = array_shift($args);
        $this->errors++;

        return $this->io->error(vsprintf($errors[$error], $args));
    }

    /**
     * Print to console final message.
     *
     * @return bool
     */
    private function finalMessage()
    {
        if (! empty($this->errors)) {
            $lines = $this->errors === 1
                ? array(
                    'An error occurred during WP Starter install,',
                    'site might be not configured properly.',
                )
                : array(
                    'Some errors occurred during WP Starter install,',
                    'site is not configured properly.',
                );

            return $this->io->block($lines, 'red', true);
        }

        return $this->io->block(array('    WP Starter finished successfully!    '), 'green', false);
    }

    /**
     *  Print a warning if a .env was found in installation folder.
     *
     * @param string $rootPath
     */
    private function envWarning($rootPath)
    {
        if (! is_file($rootPath.DIRECTORY_SEPARATOR.'.env')) {
            $lines = array(
                'Remember you need an .env file with DB settings',
                'to make your site fully functional.',
            );

            $this->io->block($lines, 'yellow', false);
        }
    }

    /**
     * Print a warning if a .gitignore was found in installation folder.
     */
    private function gitignoreWarning()
    {
        if ($this->gitignoreDone === 0) {
            $lines = array(
                'A .gitignore was found in your project folder.',
                'Be sure to ignore .env and wp-config.php files.',
            );
            $this->io->block($lines, 'yellow', false);
        }
    }

    /**
     * Prepare a path to be used in .gitignore
     *
     * @param  string $path
     * @param  string $root
     * @return string
     */
    private function gitignorePath($path, $root)
    {
        $real = realpath($root.DIRECTORY_SEPARATOR.$path);
        $rel = trim(preg_replace('|^'.preg_quote($root).'[^\\/]|', '', $real), '\\/');

        return str_replace(array('\\', '/'), '/', $rel).'/';
    }
}
