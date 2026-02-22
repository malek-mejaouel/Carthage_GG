<?php

namespace App\Service;

use App\Entity\GameMatch;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Repository\MatchRepository;
use App\Repository\TeamRepository;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SchedulerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TournamentRepository $tournamentRepository,
        private TeamRepository $teamRepository,
        private MatchRepository $matchRepository,
    ) {
    }

    public function generateForTournament(int $tournamentId, string $format = 'round_robin', array $timeSlots = [], bool $useAllTeams = false): int
    {
        $tournament = $this->tournamentRepository->find($tournamentId);
        if (!$tournament) {
            return 0;
        }

        if ($format !== 'round_robin') {
            return 0;
        }

        $teams = $useAllTeams ? $this->teamRepository->findAllTeams() : $this->getTeamsForTournament($tournament);
        if (count($teams) < 2) {
            return 0;
        }

        $assignedSlots = $this->normalizeTimeSlots($timeSlots);
        $created = $this->generateRoundRobin($tournament, $teams, $assignedSlots);
        return $created;
    }

    private function getTeamsForTournament(Tournament $tournament): array
    {
        $unique = [];
        foreach ($tournament->getMatches() as $m) {
            $a = $m->getTeamA();
            $b = $m->getTeamB();
            if ($a) {
                $unique[$a->getTeamId()] = $a;
            }
            if ($b) {
                $unique[$b->getTeamId()] = $b;
            }
        }
        if (count($unique) > 0) {
            return array_values($unique);
        }
        return $this->teamRepository->findAllTeams();
    }

    private function normalizeTimeSlots(array $timeSlots): array
    {
        $result = [];
        foreach ($timeSlots as $slot) {
            if ($slot instanceof \DateTimeInterface) {
                $result[] = $slot;
            } elseif (is_string($slot)) {
                $dt = new \DateTime($slot);
                $result[] = $dt;
            }
        }
        return $result;
    }

    private function generateRoundRobin(Tournament $tournament, array $teams, array $timeSlots): int
    {
        $existingPairs = $this->existingPairsForTournament($tournament);
        $n = count($teams);
        $created = 0;
        $slotIndex = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $teamA = $teams[$i];
                $teamB = $teams[$j];
                $key = $this->pairKey($teamA, $teamB);
                if (isset($existingPairs[$key])) {
                    continue;
                }
                $match = new GameMatch();
                $match->setTournament($tournament);
                $match->setTeamA($teamA);
                $match->setTeamB($teamB);
                if (!empty($timeSlots)) {
                    $slot = $timeSlots[$slotIndex % count($timeSlots)];
                    $match->setMatchDate($slot);
                    $slotIndex++;
                }
                $this->entityManager->persist($match);
                $created++;
            }
        }
        if ($created > 0) {
            $this->entityManager->flush();
        }
        return $created;
    }

    private function existingPairsForTournament(Tournament $tournament): array
    {
        $pairs = [];
        $existing = $this->matchRepository->findByTournament($tournament->getTournamentId());
        foreach ($existing as $m) {
            $a = $m->getTeamA();
            $b = $m->getTeamB();
            if ($a && $b) {
                $pairs[$this->pairKey($a, $b)] = true;
            }
        }
        return $pairs;
    }

    private function pairKey(Team $a, Team $b): string
    {
        $idA = (int) $a->getTeamId();
        $idB = (int) $b->getTeamId();
        if ($idA < $idB) {
            return $idA . '-' . $idB;
        }
        return $idB . '-' . $idA;
    }
}

