<?php

namespace App\Services\Sleeper;

use App\DataTransferObjects\League\LeagueData;
use App\Exceptions\SleeperApi\InsufficientDataException;
use App\Services\Sleeper\Contracts\SleeperApiInterface;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;

class LeagueDataService
{
    public function __construct(
        private SleeperApiInterface $sleeperApi
    ) {}

    public function getCompleteLeagueData(LeagueId $leagueId): LeagueData
    {
        // Fetch all required data
        $league = $this->sleeperApi->getLeague($leagueId);
        $users = $this->sleeperApi->getLeagueUsers($leagueId);
        $rosters = $this->sleeperApi->getLeagueRosters($leagueId);
        $state = $this->sleeperApi->getSportState();

        // Validate basic data
        $this->validateLeagueData($league, $users, $rosters, $state);

        // Calculate current analysis week
        $currentWeek = $this->getCurrentAnalysisWeek($state, $league->settings->playoff_week_start);

        // Fetch matchup data for all weeks
        $matchups = $this->fetchAllMatchups($leagueId, $currentWeek);

        // Return LeagueData DTO
        return LeagueData::fromSleeperData(
            $leagueId,
            $league,
            $users,
            $rosters,
            $state,
            $matchups
        );
    }

    public function validateLeagueData(object $league, array $users, array $rosters, object $state): void
    {
        $currentWeek = min($state->week, $league->settings->playoff_week_start);

        // Check for edge case where accessing array indices might fail
        if ($currentWeek <= 0) {
            throw new InsufficientDataException('Invalid current week calculated');
        }

        if (empty($users)) {
            throw new InsufficientDataException('No users found in league');
        }

        if (empty($rosters)) {
            throw new InsufficientDataException('No rosters found in league');
        }
    }

    public function getCurrentAnalysisWeek(object $state, int $playoffStart): Week
    {
        $currentWeek = min($state->week, $playoffStart);

        return new Week($currentWeek);
    }

    private function fetchAllMatchups(LeagueId $leagueId, Week $currentWeek): array
    {
        $matchups = [];

        for ($i = 1; $i < $currentWeek->toInt(); $i++) {
            $week = new Week($i);
            $weekMatchups = $this->sleeperApi->getLeagueMatchups($leagueId, $week);

            if (! empty($weekMatchups)) {
                $matchups[$i] = $weekMatchups;
            }
        }

        return $matchups;
    }
}
