<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AvlablCleanupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:avlabl-cleanup')

            // the short description shown while running "php bin/console list"
            ->setDescription('Clean up files older than 48 hours.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('To avoid having many old backup files stored on the webserver, you can call this script an all files older than 48 hours will be deleted.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Clean up starting',
            '============',
        ]);
        
        $avlabl = $this->getContainer()->get('app.avlabl_xml');
        $messages = $avlabl->removeOldFiles();
        
        foreach ($messages as $message) {
            $output->writeln($message);
        }

        $output->writeln([
            '============',
            'Finished cleaning old files',
        ]);
    }
}