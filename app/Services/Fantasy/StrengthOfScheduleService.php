<?php

namespace App\Services\Fantasy;

use Illuminate\Support\Facades\Log;

class StrengthOfScheduleService
{
    public function calculateOverallWinsLosses(array $managers): array
    {
        Log::info('=== PHASE 6: Calculating overall wins/losses and strength of schedule ===');

        // Initialize arrays
        $overallWins = [];
        $overallLosses = [];

        foreach ($managers as $manager) {
            foreach ($manager['records'] as $rosterId => $record) {
                $overallWins[$rosterId] = 0;
                $overallLosses[$rosterId] = 0;
            }
        }

        Log::info('Overall arrays initialized');

        // Calculate totals
        foreach ($managers as $manager) {
            foreach ($manager['records'] as $rosterId => $record) {
                $overallWins[$rosterId] += $record['win'];
                $overallLosses[$rosterId] += $record['loss'];
            }
        }

        Log::info('Overall wins/losses calculated', [
            'overall_wins' => $overallWins,
            'overall_losses' => $overallLosses
        ]);

        return [
            'wins' => $overallWins,
            'losses' => $overallLosses
        ];
    }

    public function rankByStrengthOfSchedule(array $overallLosses): array
    {
        // Sort by losses descending (more losses = tougher schedule)
        arsort($overallLosses);

        Log::info('Overall losses sorted (strength of schedule)', array_keys($overallLosses));

        return $overallLosses;
    }

    public function generateStrengthAnalysis(array $managers): array
    {
        $overallData = $this->calculateOverallWinsLosses($managers);
        $rankedLosses = $this->rankByStrengthOfSchedule($overallData['losses']);

        return [
            'overall_wins' => $overallData['wins'],
            'overall_losses' => $rankedLosses,
            'rankings' => array_keys($rankedLosses)
        ];
    }
}