<?php

namespace App\Command;

use App\Repository\MatchRepository;
use App\Repository\TournamentRepository;
use App\Service\SchedulerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:schedule-tournament',
    description: 'Generate matches for a tournament (round-robin by default)',
)]
final class ScheduleTournamentCommand extends Command
{
    public function __construct(
        private SchedulerService $schedulerService,
        private TournamentRepository $tournamentRepository,
        private MatchRepository $matchRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('id', mode: InputOption::VALUE_REQUIRED, description: 'Tournament ID to schedule')
            ->addOption('latest', mode: InputOption::VALUE_NONE, description: 'Schedule for latest tournament by ID')
            ->addOption('all-teams', mode: InputOption::VALUE_NONE, description: 'Use all teams in the system as participants');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getOption('id');
        $latest = (bool) $input->getOption('latest');

        if (!$id && !$latest) {
            $io->error('Provide --id=<id> or --latest');
            return Command::INVALID;
        }

        if ($latest) {
            $latestEntity = $this->tournamentRepository->findBy([], ['tournament_id' => 'DESC'], 1);
            if (empty($latestEntity)) {
                $io->warning('No tournaments found');
                return Command::SUCCESS;
            }
            $id = $latestEntity[0]->getTournamentId();
        }

        $id = (int) $id;
        $useAll = (bool) $input->getOption('all-teams');
        $created = $this->schedulerService->generateForTournament($id, 'round_robin', [], $useAll);
        $totalMatches = count($this->matchRepository->findByTournament($id));

        $io->success(sprintf(
            'Scheduled %d new matches for tournament #%d. Total matches now: %d.',
            $created,
            $id,
            $totalMatches
        ));
        return Command::SUCCESS;
    }
}
