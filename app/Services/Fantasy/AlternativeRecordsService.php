<?php

namespace App\Services\Fantasy;

use App\Services\Fantasy\Contracts\RecordsCalculatorInterface;
use App\ValueObjects\Score;

class AlternativeRecordsService implements RecordsCalculatorInterface
{
    public function calculateAlternativeRecords(array $managers): array
    {
        foreach ($managers as $rosterId => $manager) {

            foreach ($manager['records'] as $opponentRosterId => $record) {
                $opponentSchedule = $managers[$opponentRosterId]['schedule'];

                foreach ($opponentSchedule as $week => $results) {
                    $managerScore = new Score($manager['schedule'][$week]['score']);

                    if ($results['vs'] === $rosterId) {
                        // Direct matchup - compare actual scores
                        $opponentScore = new Score($managers[$results['roster_id']]['schedule'][$week]['score']);
                        $win = $this->determineMatchupResult($managerScore, $opponentScore);
                    } else {
                        // Alternative matchup - compare manager's score vs opponent's opponent
                        $opponentOpponentScore = new Score($managers[$results['vs']]['schedule'][$week]['score']);
                        $win = $this->determineMatchupResult($managerScore, $opponentOpponentScore);
                    }

                    // Update record
                    if ($win) {
                        $managers[$rosterId]['records'][$opponentRosterId]['win']++;
                    } else {
                        $managers[$rosterId]['records'][$opponentRosterId]['loss']++;
                    }
                }
            }
        }

        return $managers;
    }

    public function compareManagerAgainstSchedule(array $manager, array $scheduleOwner): array
    {
        $results = ['direct_matchups' => [], 'alternative_matchups' => []];
        $managerRosterId = $manager['roster_id'];

        foreach ($scheduleOwner['schedule'] as $week => $matchup) {
            $managerScore = new Score($manager['schedule'][$week]['score']);

            if ($matchup['vs'] === $managerRosterId) {
                // Direct matchup
                $results['direct_matchups'][$week] = [
                    'manager_score' => $managerScore->toFloat(),
                    'opponent_score' => $matchup['score'],
                    'win' => $this->determineMatchupResult($managerScore, new Score($matchup['score'])),
                ];
            } else {
                // Alternative matchup
                $results['alternative_matchups'][$week] = [
                    'manager_score' => $managerScore->toFloat(),
                    'opponent_score' => $matchup['score'],
                    'win' => $this->determineMatchupResult($managerScore, new Score($matchup['score'])),
                ];
            }
        }

        return $results;
    }

    public function determineMatchupResult(Score $managerScore, Score $opponentScore): bool
    {
        return $managerScore->isGreaterThan($opponentScore);
    }
}
