<?php

/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests;

use Composer\IO\IOInterface;
use Composer\IO\NullIO;

class TestIo extends NullIO
{
    /**
     * @var list<string>
     */
    public $outputs = [];

    /**
     * @var list<string>
     */
    public $errors = [];

    /**
     * @var int
     */
    public $verbosity;

    /**
     * @param int $verbosity
     */
    public function __construct(int $verbosity = IOInterface::NORMAL)
    {
        $this->verbosity = $verbosity;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return bool
     */
    public function hasOutput(): bool
    {
        return $this->outputs !== [];
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->hasOutput() && !$this->hasErrors();
    }

    /**
     * @return void
     */
    public function resetAllTestWrites(): void
    {
        $this->outputs = [];
        $this->errors = [];
    }

    /**
     * @param non-empty-string $regex
     * @return bool
     */
    public function hasOutputThatMatches(string $regex): bool
    {
        return $this->hasMessageThatMatches($regex, $this->outputs);
    }

    /**
     * @param non-empty-string $regex
     * @return bool
     */
    public function hasErrorThatMatches(string $regex): bool
    {
        return $this->hasMessageThatMatches($regex, $this->errors);
    }

    /**
     * @param mixed $messages
     * @param bool $newline
     * @param int $verbosity
     * @return void
     */
    public function write($messages, $newline = true, $verbosity = self::NORMAL): void
    {
        $this->executeTestWrite($messages, $newline, $verbosity, false);
    }

    /**
     * @param mixed $messages
     * @param bool $newline
     * @param int $verbosity
     * @return void
     */
    public function writeError($messages, $newline = true, $verbosity = self::NORMAL): void
    {
        $this->executeTestWrite($messages, $newline, $verbosity, true);
    }

    /**
     * @param string $regex
     * @param list<string> $messages
     * @return bool
     */
    private function hasMessageThatMatches(string $regex, array $messages): bool
    {
        foreach ($messages as $message) {
            if (preg_match($regex, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $messages
     * @param bool $newline
     * @param int $verbosity
     * @param bool $isError
     * @return void
     */
    private function executeTestWrite($messages, bool $newline, int $verbosity, bool $isError): void
    {
        if ($verbosity > $this->verbosity) {
            return;
        }
        is_string($messages) and $messages = [$messages];
        if (is_array($messages)) {
            foreach ($messages as $message) {
                $isError
                    ? $this->errors[] = $newline ? "{$message}\n" : $message
                    : $this->outputs[] = $newline ? "{$message}\n" : $message;
            }
        }
    }
}
