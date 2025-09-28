<?php

namespace App\Services\Fantasy;

use App\Services\Fantasy\Contracts\ScheduleAnalysisInterface;
use App\DataTransferObjects\League\WeeklySchedule;
use App\ValueObjects\Week;
use App\ValueObjects\RosterId;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ScheduleAnalysisService implements ScheduleAnalysisInterface
{
    public function buildScheduleFromMatchups(array $matchups): WeeklySchedule
    {
        Log::info('=== PHASE 3: Building schedule from matchups ===');

        $schedule = new WeeklySchedule();

        foreach ($matchups as $w => $week) {
            Log::debug("Processing week {$w}", ['matchup_count' => count($week)]);

            foreach ($week as $matchup) {
                $filtered = Arr::where($week, function ($value, $key) use ($matchup) {
                    return $value->matchup_id === $matchup->matchup_id && $value->roster_id !== $matchup->roster_id;
                });

                $opponent = current($filtered);

                if ($opponent) {
                    $schedule->addMatchup(
                        new Week($w),
                        new RosterId($matchup->roster_id),
                        [
                            'score' => $matchup->points,
                            'vs' => $opponent->roster_id,
                            'roster_id' => $matchup->roster_id
                        ]
                    );

                    Log::debug("Matchup processed", [
                        'week' => $w,
                        'roster_id' => $matchup->roster_id,
                        'score' => $matchup->points,
                        'vs' => $opponent->roster_id,
                        'matchup_id' => $matchup->matchup_id
                    ]);
                }
            }
        }

        Log::info('Schedule built', [
            'total_weeks' => $schedule->getWeekCount(),
            'sample_week' => !empty($schedule->getAllWeeks()) ? $schedule->getAllWeeks()[0] : 'No schedule'
        ]);

        return $schedule;
    }

    public function addSchedulesToManagers(array $managers, WeeklySchedule $schedule): array
    {
        Log::info('=== PHASE 4: Adding schedules and initializing records for managers ===');

        foreach ($managers as $rosterId => $manager) {
            Log::debug("Processing manager schedules", [
                'roster_id' => $rosterId,
                'name' => $manager['name'] ?? 'Unknown'
            ]);

            // Add schedule
            $managerSchedule = $schedule->getManagerSchedule(new RosterId($rosterId));
            $managers[$rosterId]['schedule'] = $managerSchedule;

            Log::debug("Schedule added for manager {$rosterId}", [
                'weeks_scheduled' => count($managerSchedule)
            ]);

            // Initialize records against all managers
            $managers[$rosterId]['records'] = $this->initializeRecordsForManager($managers);

            Log::debug("Records initialized for manager {$rosterId}", [
                'opponent_count' => count($managers[$rosterId]['records'])
            ]);
        }

        Log::info('Schedules and records initialized for all managers');

        return $managers;
    }

    public function initializeRecords(array $managers): array
    {
        foreach ($managers as $rosterId => $manager) {
            $managers[$rosterId]['records'] = $this->initializeRecordsForManager($managers);
        }

        return $managers;
    }

    private function initializeRecordsForManager(array $allManagers): array
    {
        $records = [];

        foreach ($allManagers as $rosterId => $manager) {
            $records[$rosterId] = [
                'name' => $manager['name'],
                'roster_id' => $rosterId,
                'win' => 0,
                'loss' => 0
            ];
        }

        return $records;
    }
}