<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use App\Entity\Waypoint;
use App\Repository\WaypointRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:find-dupes',
    description: 'Find duplicated entries'
)]
class FindDupesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WaypointRepository $waypointRepository
    )
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int
    {
        $io = new SymfonyStyle($input, $output);
        $waypoints = $this->waypointRepository->findAll();
        $progressBar = new ProgressBar($output, count($waypoints));
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $choices = [
            'Remove A',
            'Remove B',
            'Change name A with B',
            'Change name B with A',
            'Skip',
        ];

        $question = new ChoiceQuestion('Please select [Skip]', $choices, 4);
        $question->setErrorMessage('Choice %s is invalid.');

        $removals = 0;

        foreach ($waypoints as $waypoint) {
            $removals += $this->findDuplicatesForWaypoint($waypoint, $waypoints, $io, $helper, $input, $output, $choices, $question);
            $progressBar->advance();
        }

        $progressBar->finish();

        if ($removals !== 0) {
            $io->warning(sprintf('%d duplicates have been removed.', $removals));
        } else {
            $io->success('Database is clean :)');
        }

        return Command::SUCCESS;
    }

    /**
     * @param Waypoint[] $waypoints
     * @param string[] $choices
     */
    private function findDuplicatesForWaypoint(
        Waypoint $waypoint,
        array $waypoints,
        SymfonyStyle $io,
        QuestionHelper $helper,
        InputInterface $input,
        OutputInterface $output,
        array $choices,
        ChoiceQuestion $question
    ): int {
        foreach ($waypoints as $test) {
            if ($test->getId() === $waypoint->getId()) {
                continue;
            }

            if ($test->getGuid() === $waypoint->getGuid()) {
                $io->warning('@TODO Duplicated GUID found for: '.$waypoint->getName());
            }

            if ($test->getLat() === $waypoint->getLat() && $test->getLon() === $waypoint->getLon()) {
                return $this->handleDuplicate(
                    $waypoint,
                    $test,
                    $io,
                    $helper,
                    $input,
                    $output,
                    $choices,
                    $question
                );
            }
        }

        return 0;
    }

    /**
     * @param string[] $choices
     */
    private function handleDuplicate(
        Waypoint $waypoint,
        Waypoint $test,
        SymfonyStyle $io,
        QuestionHelper $helper,
        InputInterface $input,
        OutputInterface $output,
        array $choices,
        ChoiceQuestion $question
    ): int {
        $io->text([
            '',
            sprintf('A: %s - %d', $waypoint->getName(), $waypoint->getId()),
            sprintf('B: %s - %d', $test->getName(), $test->getId()),
        ]);

        if ($waypoint->getName() === $test->getName()) {
            $this->entityManager->remove($test);
            $this->entityManager->flush();
            return 1;
        }

        $io->warning('Name mismatch!');
        /** @var string $choice */
        $choice = $helper->ask($input, $output, $question);

        return $this->applyUserChoice($choice, $waypoint, $test, $choices);
    }

    /**
     * @param string[] $choices
     */
    private function applyUserChoice(
        string $choice,
        Waypoint $waypoint,
        Waypoint $test,
        array $choices
    ): int {
        if ($choice === $choices[0]) {
            $this->entityManager->remove($waypoint);
            $this->entityManager->flush();
            return 1;
        }

        if ($choice === $choices[1]) {
            $this->entityManager->remove($test);
            $this->entityManager->flush();
            return 1;
        }

        if ($choice === $choices[2]) {
            $waypoint->setName((string)$test->getName());
            $this->entityManager->persist($waypoint);
            $this->entityManager->flush();
        } elseif ($choice === $choices[3]) {
            $test->setName((string)$waypoint->getName());
            $this->entityManager->persist($test);
            $this->entityManager->flush();
        }

        return 0;
    }
}
