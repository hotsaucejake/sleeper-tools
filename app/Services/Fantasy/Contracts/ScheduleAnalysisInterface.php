<?php

namespace App\Services\Fantasy\Contracts;

use App\DataTransferObjects\League\WeeklySchedule;

interface ScheduleAnalysisInterface
{
    /**
     * Build weekly schedule from raw matchup data
     */
    public function buildScheduleFromMatchups(array $matchups): WeeklySchedule;

    /**
     * Add schedules to managers and initialize records
     */
    public function addSchedulesToManagers(array $managers, WeeklySchedule $schedule): array;

    /**
     * Initialize empty records structure for all managers
     */
    public function initializeRecords(array $managers): array;
}