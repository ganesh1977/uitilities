<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NotifyBookingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:notify-booking')

            // the short description shown while running "php bin/console list"
            ->setDescription('Notify on bookings')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('With this command notifications on bookings made will be sent to IT and Finance.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $minutes = 60;
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Dynamic Packaging bookings made the last ' . $minutes . ' minutes',
            '============',
        ]);
        
        $atcore = $this->getContainer()->get('app.atcore');
        $reservations = $atcore->getDynamicPackagingReservations($minutes);
        
        if (count($reservations)) {
            $message = \Swift_Message::newInstance()
                ->setSubject('New Dynamic Packaging bookings')
                ->setFrom(['utils@primerait.com' => 'Utils'])
                ->setTo([
                    'kand@primeragroup.com'
                ])
                ->setBody(
                    $this->getContainer()->get('templating')->render('emails/bookings.html.twig', [
                        'minutes' => $minutes,
                        'reservations' => $reservations
                    ]),
                'text/html');
            $this->getContainer()->get('mailer')->send($message);
        }
        
        foreach ($reservations as $reservation) {
            $output->writeln('Reservation ' . $reservation['RES_ID'] . ' created ' . $reservation['ORIGIN_DT'] . ' has booking status ' . ($reservation['BKG_STS'] == 'OPT' ? 'OPT or BKD' : $reservation['BKG_STS']));
        }

        $output->writeln([
            '============',
            'No more bookings found...',
        ]);
    }
}