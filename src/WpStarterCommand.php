<?php
/*
 * This file is part of the WP Starter package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package WeCodeMore\WpStarter
 */
final class WpStarterCommand extends BaseCommand
{
    /**
     * @inheritdoc
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('wpstarter')
            ->setDescription('Run WP Starter installation workflow.')
            ->addArgument(
                'steps',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Which setps to run. (Separate more steps names with a space). Defaults all.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $plugin = new ComposerPlugin();
            $plugin->activate($this->getComposer(false, false), $this->getIO());
            $plugin->run(null, $input->getArgument('steps') ?: []);

            return 0;
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return 1;
        }
    }
}
