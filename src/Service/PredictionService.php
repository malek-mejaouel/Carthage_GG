<?php

namespace App\Service;

use App\Repository\MatchRepository;
use App\Entity\Team;

class PredictionService
{
    public function __construct(private MatchRepository $matchRepo) {}

    /**
     * @return array{
     *   winner: Team,
     *   probability: float,
     *   stats: array{
     *     teamA: array{wins:int,losses:int,draws:int,win_rate:float,total_score:int,avg_score:float},
     *     teamB: array{wins:int,losses:int,draws:int,win_rate:float,total_score:int,avg_score:float}
     *   }
     * }
     */
    public function predictWinner(Team $teamA, Team $teamB): array
    {
        $statsA = $this->calculateTeamStats($teamA);
        $statsB = $this->calculateTeamStats($teamB);

        $scoreA = $statsA['avg_score'] * 0.6 + $statsA['win_rate'] * 0.4;
        $scoreB = $statsB['avg_score'] * 0.6 + $statsB['win_rate'] * 0.4;

        $totalScore = $scoreA + $scoreB;
        $probA = $totalScore > 0 ? round(($scoreA / $totalScore) * 100, 2) : 50;

        return [
            'winner' => $probA > 50 ? $teamA : $teamB,
            'probability' => max($probA, 100 - $probA),
            'stats' => [
                'teamA' => $statsA,
                'teamB' => $statsB,
            ],
        ];
    }

    /**
     * @return array{wins:int,losses:int,draws:int,win_rate:float,total_score:int,avg_score:float}
     */
    private function calculateTeamStats(Team $team): array
    {
        $matches = $this->matchRepo->findCompletedByTeam($team);
        $totalMatches = count($matches);
        
        if ($totalMatches === 0) {
            return [
                'wins' => 0,
                'losses' => 0,
                'draws' => 0,
                'win_rate' => 0,
                'total_score' => 0,
                'avg_score' => 0,
            ];
        }

        $wins = 0;
        $losses = 0;
        $draws = 0;
        $totalScore = 0;

        foreach ($matches as $match) {
            // Déterminer si l'équipe est A ou B
            if ($match->getTeamA() === $team) {
                $score = $match->getScoreTeamA();
                $opponentScore = $match->getScoreTeamB();
            } else {
                $score = $match->getScoreTeamB();
                $opponentScore = $match->getScoreTeamA();
            }

            $totalScore += $score;

            if ($score > $opponentScore) {
                $wins++;
            } elseif ($score < $opponentScore) {
                $losses++;
            } else {
                $draws++;
            }
        }

        $avgScore = $totalScore / $totalMatches;
        $winRate = ($wins / $totalMatches) * 100;

        return [
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
            'win_rate' => round($winRate, 2),
            'total_score' => $totalScore,
            'avg_score' => round($avgScore, 2),
        ];
    }
}
