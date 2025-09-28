<?php

namespace App\Services\Analysis\Contracts;

use App\DataTransferObjects\Analysis\PerformanceAwardsResults;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;

interface PerformanceAwardsInterface
{
    /**
     * Analyze performance awards for a specific week in a league
     */
    public function analyzeWeeklyPerformance(LeagueId $leagueId, Week $week): PerformanceAwardsResults;
}