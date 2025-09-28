<?php

namespace App\Services\Fantasy;

use App\DataTransferObjects\League\WeeklySchedule;
use App\Services\Fantasy\Contracts\ScheduleAnalysisInterface;
use App\ValueObjects\RosterId;
use App\ValueObjects\Week;
use Illuminate\Support\Arr;

class ScheduleAnalysisService implements ScheduleAnalysisInterface
{
    public function buildScheduleFromMatchups(array $matchups): WeeklySchedule
    {
        $schedule = new WeeklySchedule;

        foreach ($matchups as $w => $week) {
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
                            'roster_id' => $matchup->roster_id,
                        ]
                    );
                }
            }
        }

        return $schedule;
    }

    public function addSchedulesToManagers(array $managers, WeeklySchedule $schedule): array
    {
        foreach ($managers as $rosterId => $manager) {
            // Add schedule
            $managerSchedule = $schedule->getManagerSchedule(new RosterId($rosterId));
            $managers[$rosterId]['schedule'] = $managerSchedule;

            // Initialize records against all managers
            $managers[$rosterId]['records'] = $this->initializeRecordsForManager($managers);
        }

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
                'loss' => 0,
            ];
        }

        return $records;
    }
}
