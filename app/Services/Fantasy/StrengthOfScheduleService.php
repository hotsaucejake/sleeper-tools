<?php

namespace App\Services\Fantasy;

class StrengthOfScheduleService
{
    public function calculateOverallWinsLosses(array $managers): array
    {
        // Initialize arrays
        $overallWins = [];
        $overallLosses = [];

        foreach ($managers as $manager) {
            foreach ($manager['records'] as $rosterId => $record) {
                $overallWins[$rosterId] = 0;
                $overallLosses[$rosterId] = 0;
            }
        }

        // Calculate totals
        foreach ($managers as $manager) {
            foreach ($manager['records'] as $rosterId => $record) {
                $overallWins[$rosterId] += $record['win'];
                $overallLosses[$rosterId] += $record['loss'];
            }
        }

        return [
            'wins' => $overallWins,
            'losses' => $overallLosses,
        ];
    }

    public function rankByStrengthOfSchedule(array $overallLosses): array
    {
        // Sort by losses descending (more losses = tougher schedule)
        arsort($overallLosses);

        return $overallLosses;
    }

    public function generateStrengthAnalysis(array $managers): array
    {
        $overallData = $this->calculateOverallWinsLosses($managers);
        $rankedLosses = $this->rankByStrengthOfSchedule($overallData['losses']);

        return [
            'overall_wins' => $overallData['wins'],
            'overall_losses' => $rankedLosses,
            'rankings' => array_keys($rankedLosses),
        ];
    }
}
