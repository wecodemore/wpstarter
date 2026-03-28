<?php

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests;

use Composer\Factory;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\Output;

class CollectingOutput extends Output
{
    public string $output = '';
    /** @var list<string> */
    public array $lines = [];

    /**
     * @param int $verbosity
     */
    public function __construct(int $verbosity)
    {
        parent::__construct(
            $verbosity,
            false,
            new OutputFormatter(false, Factory::createAdditionalStyles())
        );
    }

    /**
     * @param string $message
     * @param bool $newline
     * @return void
     */
    protected function doWrite(string $message, bool $newline): void
    {
        if (!$newline && $this->lines !== []) {
            $last = array_pop($this->lines);
            $message = $last . $message;
        }

        $this->lines[] = $message;
        $this->output = implode("\n", $this->lines);
    }
}
