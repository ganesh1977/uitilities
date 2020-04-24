<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AvlablDownloadCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:avlabl-download')

            // the short description shown while running "php bin/console list"
            ->setDescription('Downloading latest AVLABL files.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to download all the latest AVLABL files for quicker backup if something goes wrong.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Download starting',
            '============',
        ]);
        
        $avlabl = $this->getContainer()->get('app.avlabl_xml');
        $messages = $avlabl->downloadLatestFiles();
        
        foreach ($messages as $message) {
            $output->writeln($message);
        }

        $output->writeln([
            '============',
            'Finished downloading files',
        ]);
    }
}