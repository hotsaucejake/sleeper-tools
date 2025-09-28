<?php

namespace App\DataTransferObjects\League;

use App\ValueObjects\RosterId;
use App\ValueObjects\Week;

class WeeklySchedule
{
    public function __construct(
        private array $schedule = []
    ) {}

    public function addMatchup(Week $week, RosterId $rosterId, array $matchupData): void
    {
        $this->schedule[$week->toInt()][$rosterId->toInt()] = $matchupData;
    }

    public function getWeekSchedule(Week $week): array
    {
        return $this->schedule[$week->toInt()] ?? [];
    }

    public function getManagerSchedule(RosterId $rosterId): array
    {
        $managerSchedule = [];

        foreach ($this->schedule as $week => $weekSchedule) {
            if (isset($weekSchedule[$rosterId->toInt()])) {
                $managerSchedule[$week] = $weekSchedule[$rosterId->toInt()];
            }
        }

        return $managerSchedule;
    }

    public function getAllWeeks(): array
    {
        return array_keys($this->schedule);
    }

    public function toArray(): array
    {
        return $this->schedule;
    }

    public function getWeekCount(): int
    {
        return count($this->schedule);
    }
}
