<?php

declare(strict_types=1);

namespace WeCodeMore\WpStarter\Tests;

use Composer\Factory;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\Output;

class CollectingOutput extends Output
{
    public string $output = '';

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
        $this->output .= $message . ($newline ? "\n" : '');
    }
}
