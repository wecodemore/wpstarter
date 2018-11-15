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
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WeCodeMore\WpStarter\Util\SelectedStepsFactory;

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
            ->setDescription('Run WP Starter workflow.')
            ->addOption(
                'skip',
                null,
                InputOption::VALUE_NONE,
                'Enable opt-out mode: provided step names are those to skip, not those to run.'
            )
            ->addOption(
                'skip-custom',
                null,
                InputOption::VALUE_NONE,
                'Skip any step defined in "custom-steps" setting.'
            )
            ->addOption(
                'ignore-skip-config',
                null,
                InputOption::VALUE_NONE,
                'Ignore "skip-steps" config.'
            )
            ->addArgument(
                'steps',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Which steps to run (or to skip). Separate step names with a space.'
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

        $composer = $this->getComposer(true, false);
        if ($composer->getPackage()->getType() === ComposerPlugin::EXTENSIONS_TYPE) {
            $this->writeError(
                $output,
                'WP Starter command does not work when root package is of type'
                . ' "wpstarter-extension".'
            );

            return 1;
        }

        try {
            ComposerPlugin::setupAutoload();

            $plugin = new ComposerPlugin();
            $plugin->activate($composer, $this->getIO());

            $skip = $input->hasOption('skip')
                && $input->getOption('skip');
            $skipCustom = $input->hasOption('skip-custom')
                && $input->getOption('skip-custom');
            $ignoreSkipConfig = $input->hasOption('ignore-skip-config')
                && $input->getOption('ignore-skip-config');

            $flags = SelectedStepsFactory::MODE_COMMAND;
            $skip and $flags |= SelectedStepsFactory::SKIP;
            $skipCustom and $flags |= SelectedStepsFactory::SKIP_CUSTOM;
            $ignoreSkipConfig and $flags |= SelectedStepsFactory::IGNORE_SKIP_CONFIG;

            $selected = $input->getArgument('steps') ?: [];

            $plugin->run(new SelectedStepsFactory($flags, ...$selected));

            return 0;
        } catch (\Throwable $throwable) {
            $this->writeError($output, $throwable->getMessage());

            return 1;
        }
    }

    /**
     * @param OutputInterface $output
     * @param string $message
     */
    private function writeError(OutputInterface $output, string $message)
    {
        $words = explode(' ', $message);
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            if (strlen($line . $word) < 60) {
                $line .= $line ? " {$word}" : $word;
                continue;
            }

            $lines[] = "  {$line}  ";
            $line = $word;
        }

        $line and $lines[] = "  {$line}  ";

        $lenMax = max(array_map('strlen', $lines));
        $empty = '<error>' . str_repeat(' ', $lenMax) . '</error>';
        $errors = ['', $empty];
        foreach ($lines as $line) {
            $lineLen = strlen($line);
            ($lineLen < $lenMax) and $line .= str_repeat(' ', $lenMax - $lineLen);
            $errors[] = "<error>{$line}</error>";
        }

        $errors[] = $empty;
        $errors[] = '';

        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        $output->writeln($errors);
    }
}
