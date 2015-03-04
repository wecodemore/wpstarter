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

use Composer\IO\IOInterface;
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
     * @var string[] Files that have to build using templates and variables
     */
    private static $files = array('wp-config.php', 'index.php', '.gitignore');

    /**
     * @var string[] Keys of the path object that must be checked
     */
    private static $paths = array('root', 'vendor', 'wp', 'starter');

    /**
     * @var \Composer\IO\IOInterface
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
     * Construct. Just for DI.
     *
     * @param \Composer\IO\IOInterface $io
     * @param \WCM\WPStarter\Renderer  $renderer
     * @param \WCM\WPStarter\Salter    $salter
     */
    public function __construct(IOInterface $io, Renderer $renderer = null, Salter $salter = null)
    {
        $this->io = $io;
        $this->renderer = $renderer ?: new Renderer();
        $this->salter = $salter ?: new Salter();
    }

    /**
     * Builds wp-config.php and copy it to root folder.
     * Also copies additional file if in installation mode.
     *
     * @param  \ArrayAccess $paths
     * @return bool         true on success, false on failure
     */
    public function build(ArrayAccess $paths)
    {
        $this->progress('start');
        if ($this->checkPaths($paths)) {
            $this->progress('paths_checked');
        } else {
            return $this->error('error');
        }
        foreach (self::$files as $file) {
            if ($this->saveFile($this->buildFile($paths, $file), $paths['root'], $file)) {
                $this->progress('file_done', $file);
            }
        }
        if ($this->copy($paths, '.env.example')) {
            $this->progress('env_done');
        }
        if ($this->moveContent($paths) === true) {
            $this->progress('move_done');
        }

        return $this->errors > 0
            ? $this->error($this->errors === 1 ? 'error' : 'errors')
            : ($this->progress('done') and $this->progress('end'));
    }

    /**
     * Check paths.
     *
     * @param  \ArrayAccess $paths
     * @return bool         true on success, false on failure
     */
    private function checkPaths(ArrayAccess $paths)
    {
        foreach (self::$paths as $key) {
            if (! isset($paths[$key]) || ! is_dir($paths[$key])) {
                return $this->error('bad_path', $key);
            } elseif (
                $key === 'wp'
                && ! file_exists($paths[$key].DIRECTORY_SEPARATOR.'wp-settings.php')
            ) {
                return $this->error('bad_wp');
            }
        }

        return true;
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
        static $vars;
        if (is_null($vars)) {
            $vars = array(
                'VENDOR_PATH'        => $paths['vendor'],
                'WP_INSTALL_PATH'    => $paths['wp'],
                'WP_INSTALL_SUBDIR'  => $this->subdir($paths['root'], $paths['wp']),
                'VENDOR_PATH_SUBDIR' => $this->subdir($paths['root'], $paths['vendor']),
                'WP_CONTENT_SUBDIR'  => is_string($paths['content'])
                    ? $this->subdir($paths['root'], $paths['content'])
                    : 'wp-content',
            );
        }
        $templateFile = implode(DIRECTORY_SEPARATOR,
            array($paths['starter'], 'templates', $fileName));
        if (! is_readable($templateFile)) {
            return $this->error('create', $fileName);
        }
        $template = file_get_contents($templateFile);
        /** @var string $content */
        $content = $this->renderer->render($template, array_merge($this->salter->keys(), $vars));
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
        if (! is_dir(dirname($paths['content']))) {
            return $this->error('bad_path', 'content');
        }
        if (! is_dir($paths['content']) && ! mkdir($paths['content'], 0755)) {
            return $this->error('create', $paths['content']);
        }
        $space = str_repeat(' ', 55);
        $len = strlen($paths['content']);
        $lines = array(
            '  <question>'.$space,
            '  QUESTION                                             ',
            '  Do you want to move default plugins and themes from  ',
            '  WordPress package wp-content dir to content folder:  ',
            '  "'.$paths['content'].'"'.str_repeat(' ', $len < 51 ? 51 - $len : 0),
            $space.'</question>',
        );
        $question = PHP_EOL.implode('</question>'.PHP_EOL.'  <question>', $lines);
        $prompt = PHP_EOL.'    <option=bold>Y</option=bold> or <option=bold>N</option=bold> [Y] ';
        if (! $this->io->askConfirmation($question.PHP_EOL.$prompt, true)) {
            $this->progress('skip_content');

            return false;
        }
        $from = $paths['wp'].DIRECTORY_SEPARATOR.'wp-content';

        return $this->moveItems(glob($from.'/*'), $from, $paths['content']);
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
     * Print progress messages to console.
     *
     * @return bool always true
     */
    private function progress()
    {
        $messages = array(
            'start'         => '<comment>WP Starter is going to start installation...</comment>',
            'paths_checked' => '  - <info>OK</info> all paths recognized properly',
            'file_done'     => '  - <info>OK</info> <comment>%s</comment> generated and saved.',
            'env_done'      => '  - <info>OK</info> <comment>.env sample file</comment> copied in'
                .' project root folder.',
            'move_done'     => '  - <info>OK</info> WordPress content directory moved.',
            'skip_content'  => '  - <comment>WordPress content directory move skipped.</comment>',
            'done'          => '  WP Starter finished successfully!'.str_repeat(' ', 20),
            'end'           => '  <comment>Remember you need an .env file with -at least- DB settings'
                .PHP_EOL.'  to make your site fully functional.</comment>',
        );
        $args = func_get_args();
        $msg = array_shift($args);
        $txt = $messages[$msg];
        if ($msg === 'done') {
            $space = '  <bg=green>'.str_repeat(' ', 55).'</bg=green>';
            $txt = $space.PHP_EOL."  <bg=green;fg=black>{$txt}</bg=green;fg=black>".PHP_EOL.$space;
        }
        $this->io->write(vsprintf($txt, $args));

        return true;
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
            'errors'   => 'Some errors occurred during WP Starter install,      </error>'
                .PHP_EOL.'  <error>  site is not configured properly.                   ',
            'error'    => 'An error occurred during WP Starter install,         </error>'
                .PHP_EOL.'  <error>  site might be not configured properly.             ',
        );
        $args = func_get_args();
        $error = array_shift($args);
        $this->errors++;
        $text = '  <error>  '.vsprintf($errors[$error], $args).'  </error>';
        if (in_array($error, array('errors', 'error'), true)) {
            $space = '  <error>'.str_repeat(' ', 55).'</error>';
            $text = $space.PHP_EOL.$text.PHP_EOL.$space;
        }
        $this->io->writeError(PHP_EOL.$text);

        return false;
    }

    /**
     * Takes 2 absolute paths, one contained in the other) and return the relative part.
     * Remove leading and trailing slashes.
     *
     * @param  string $root containing path
     * @param  string $path contained path
     * @return string relative path
     */
    private function subdir($root, $path)
    {
        $normRoot = trim(str_replace('\\', '/', $root), '/');
        $normPath = trim(str_replace('\\', '/', $path), '/');

        return trim(str_replace($normRoot, '', $normPath), '/');
    }
}
