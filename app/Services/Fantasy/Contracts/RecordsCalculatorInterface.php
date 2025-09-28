<?php

namespace App\Services\Fantasy\Contracts;

use App\ValueObjects\Score;

interface RecordsCalculatorInterface
{
    /**
     * Calculate alternative records for all managers
     */
    public function calculateAlternativeRecords(array $managers): array;

    /**
     * Compare a manager against a specific schedule
     */
    public function compareManagerAgainstSchedule(array $manager, array $scheduleOwner): array;

    /**
     * Determine if manager wins against opponent based on scores
     */
    public function determineMatchupResult(Score $managerScore, Score $opponentScore): bool;
}