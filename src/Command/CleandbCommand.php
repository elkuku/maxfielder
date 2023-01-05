<?php

namespace App\Command;

use App\Repository\WaypointRepository;
use App\Service\WayPointHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleandb',
    description: 'Cleanup the database'
)]
class CleandbCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WaypointRepository $waypointRepository,
        private readonly WayPointHelper $wayPointHelper
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $errorCount = 0;
        $warningCount = 0;
        $io = new SymfonyStyle($input, $output);

        $waypoints = $this->waypointRepository->findAll();

        $progressBar = new ProgressBar($output, count($waypoints));

        foreach ($waypoints as $waypoint) {
            if (!$waypoint->getLat() || !$waypoint->getLon()) {
                $io->error(
                    sprintf('"%s" missing location', $waypoint->getName())
                );
                $errorCount++;
                $this->entityManager->remove($waypoint);
            }

            if (!$waypoint->getName()) {
                $io->error(sprintf('"%s" missing name', $waypoint->getName()));
                $errorCount++;
                $this->entityManager->remove($waypoint);
            }

            if ('undefined' === $waypoint->getName()) {
                $io->error(sprintf('"%s" name', $waypoint->getName()));
                $errorCount++;
                $this->entityManager->remove($waypoint);
            }

            $cleanName = $this->wayPointHelper->cleanName(
                (string) $waypoint->getName()
            );

            if ($waypoint->getName() !== $cleanName) {
                $io->warning(
                    sprintf(
                        '"%s" dirty title "%s" clean title',
                        $waypoint->getName(),
                        $cleanName
                    )
                );
                $warningCount++;
                $waypoint->setName($cleanName);
                $this->entityManager->persist($waypoint);
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();

        $progressBar->finish();

        if ($errorCount) {
            $io->error(sprintf('There have been %d errors', $errorCount));
        }
        if ($warningCount) {
            $io->warning(sprintf('There have been %d warnings', $warningCount));
        }

        if (!$errorCount && !$warningCount) {
            $io->success('Database is clean.');

            return Command::SUCCESS;
        }

        return Command::FAILURE;
    }
}
