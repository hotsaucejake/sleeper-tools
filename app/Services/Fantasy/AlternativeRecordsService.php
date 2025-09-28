<?php

namespace App\Services\Fantasy;

use App\Services\Fantasy\Contracts\RecordsCalculatorInterface;
use App\ValueObjects\Score;
use Illuminate\Support\Facades\Log;

class AlternativeRecordsService implements RecordsCalculatorInterface
{
    public function calculateAlternativeRecords(array $managers): array
    {
        Log::info('=== PHASE 5: Calculating alternative records (CORE ALGORITHM) ===');

        foreach ($managers as $rosterId => $manager) {
            Log::debug("Calculating alternative records for manager", [
                'roster_id' => $rosterId,
                'name' => $manager['name'] ?? 'Unknown'
            ]);

            foreach ($manager['records'] as $opponentRosterId => $record) {
                Log::debug("  Comparing against schedule of roster {$opponentRosterId}");

                $opponentSchedule = $managers[$opponentRosterId]['schedule'];

                foreach ($opponentSchedule as $week => $results) {
                    $managerScore = new Score($manager['schedule'][$week]['score']);

                    if ($results['vs'] === $rosterId) {
                        // Direct matchup - compare actual scores
                        $opponentScore = new Score($managers[$results['roster_id']]['schedule'][$week]['score']);
                        $win = $this->determineMatchupResult($managerScore, $opponentScore);

                        Log::debug("    Week {$week} - Direct matchup", [
                            'manager_score' => $managerScore->toFloat(),
                            'opponent_score' => $opponentScore->toFloat(),
                            'result' => $win ? 'WIN' : 'LOSS'
                        ]);
                    } else {
                        // Alternative matchup - compare manager's score vs opponent's opponent
                        $opponentOpponentScore = new Score($managers[$results['vs']]['schedule'][$week]['score']);
                        $win = $this->determineMatchupResult($managerScore, $opponentOpponentScore);

                        Log::debug("    Week {$week} - Alternative matchup", [
                            'manager_score' => $managerScore->toFloat(),
                            'opponent_opponent_score' => $opponentOpponentScore->toFloat(),
                            'result' => $win ? 'WIN' : 'LOSS'
                        ]);
                    }

                    // Update record
                    if ($win) {
                        $managers[$rosterId]['records'][$opponentRosterId]['win']++;
                    } else {
                        $managers[$rosterId]['records'][$opponentRosterId]['loss']++;
                    }
                }
            }

            Log::debug("Alternative records calculated for {$rosterId}", [
                'total_opponents' => count($manager['records'])
            ]);
        }

        Log::info('Alternative records calculation completed');

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
                    'win' => $this->determineMatchupResult($managerScore, new Score($matchup['score']))
                ];
            } else {
                // Alternative matchup
                $results['alternative_matchups'][$week] = [
                    'manager_score' => $managerScore->toFloat(),
                    'opponent_score' => $matchup['score'],
                    'win' => $this->determineMatchupResult($managerScore, new Score($matchup['score']))
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