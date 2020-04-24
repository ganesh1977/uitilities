<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GeckoBoardCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:geckoboard')

            // the short description shown while running "php bin/console list"
            ->setDescription('Create and update GeckoBoard datasets')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Specify dataset and command (create, append, replace or delete)')

            ->addArgument('dataset', InputArgument::REQUIRED, 'Which dataset (sales) do you want to work with?')

            ->addArgument('action', InputArgument::REQUIRED, 'Which action (create, append, replace, delete) do you want to do?')

            ->addOption('daysback', null, InputOption::VALUE_REQUIRED, 'How many days back to append data from?', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $geckoboard = $this->getContainer()->get('app.geckoboard');
        $response = null;

        if ($input->getArgument('dataset') == 'sales') {
            $output->writeln([
                'Dataset: sales',
                '========================',
            ]);
            
            if ($input->getArgument('action') == 'create') {
                $output->writeln('Create command executed!');
                
                $response = $geckoboard->createSalesDataset();
            } elseif ($input->getArgument('action') == 'delete') {
                $output->writeln('Create command executed!');
            
                $response = $geckoboard->deleteSalesDataset();
            } elseif ($input->getArgument('action') == 'append') {
                $output->writeln('Append command executed!');
                
                $response = $geckoboard->appendSalesDataset($input->getOption('daysback'));
            } else {
                $output->writeln('Unknown action!');
            }
            
            if ($response) {
                $output->writeln([
                    'Status code: ' . $response->getStatusCode(),
                    'Body: ' . $response->getBody(),
                ]);
            }

        } elseif ($input->getArgument('dataset') == 'sales.average') {
            $output->writeln([
                'Dataset: sales average',
                '========================',
            ]);
            
            if ($input->getArgument('action') == 'create') {
                $output->writeln('Create command executed!');
                
                $response = $geckoboard->createSalesAverageDataset();
            } elseif ($input->getArgument('action') == 'delete') {
                $output->writeln('Create command executed!');
            
                $response = $geckoboard->deleteSalesAverageDataset();
            } elseif ($input->getArgument('action') == 'append') {
                $output->writeln('Append command executed!');
                
                $response = $geckoboard->appendSalesAverageDataset($input->getOption('daysback'));
            } else {
                $output->writeln('Unknown action!');
            }
            
            if ($response) {
                $output->writeln([
                    'Status code: ' . $response->getStatusCode(),
                    'Body: ' . $response->getBody(),
                ]);
            }
            

        } else {
            $output->writeln('Unrecognized dataset!');
        }
        
        $output->writeln([
            '========================',
            'Finished',
        ]);
    }
}