<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adds 'wpstarter' command to Composer.
 */
final class WpStarterCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('wpstarter')
            ->setDescription('Run WP Starter installation workflow.')
            ->addOption(
                'skip',
                null,
                InputOption::VALUE_NONE,
                'Enable skip mode, which means provided steps are those to skip, not to run.'
            )
            ->addArgument(
                'steps',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Which steps to run (or to skip in skip mode). Separate more steps names with a space.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // phpcs:enable

        try {
            $plugin = new ComposerPlugin();
            $plugin->activate($this->getComposer(false, false), $this->getIO());

            $selected = $input->getArgument('steps') ?: [];
            $skipMode = $input->hasOption('skip') && $input->getOption('skip');

            $plugin->run(null, $selected, $selected && $skipMode);

            return 0;
        } catch (\Throwable $throwable) {
            $output->writeln($throwable->getMessage());

            return 1;
        }
    }
}
