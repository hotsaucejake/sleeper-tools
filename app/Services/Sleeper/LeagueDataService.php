<?php

namespace App\Services\Sleeper;

use App\Services\Sleeper\Contracts\SleeperApiInterface;
use App\DataTransferObjects\League\LeagueData;
use App\DataTransferObjects\League\WeeklySchedule;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;
use App\Exceptions\SleeperApi\InsufficientDataException;
use Illuminate\Support\Facades\Log;

class LeagueDataService
{
    public function __construct(
        private SleeperApiInterface $sleeperApi
    ) {}

    public function getCompleteLeagueData(LeagueId $leagueId): LeagueData
    {
        Log::info('=== FETCHING COMPLETE LEAGUE DATA ===', ['league_id' => $leagueId->toString()]);

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

        Log::info('League data fetch completed', [
            'league_id' => $leagueId->toString(),
            'weeks_fetched' => count($matchups),
            'managers_count' => count($rosters)
        ]);

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

        Log::debug('League data validation passed', [
            'current_week' => $currentWeek,
            'users_count' => count($users),
            'rosters_count' => count($rosters)
        ]);
    }

    public function getCurrentAnalysisWeek(object $state, int $playoffStart): Week
    {
        $currentWeek = min($state->week, $playoffStart);

        Log::debug('Analysis week calculated', [
            'sport_state_week' => $state->week,
            'playoff_start' => $playoffStart,
            'analysis_week' => $currentWeek
        ]);

        return new Week($currentWeek);
    }

    private function fetchAllMatchups(LeagueId $leagueId, Week $currentWeek): array
    {
        $matchups = [];

        Log::debug('Fetching matchups for analysis', [
            'league_id' => $leagueId->toString(),
            'weeks_to_fetch' => $currentWeek->toInt() - 1
        ]);

        for ($i = 1; $i < $currentWeek->toInt(); $i++) {
            $week = new Week($i);
            $weekMatchups = $this->sleeperApi->getLeagueMatchups($leagueId, $week);

            if (!empty($weekMatchups)) {
                $matchups[$i] = $weekMatchups;
            }
        }

        Log::info('All matchups fetched', [
            'total_weeks' => count($matchups),
            'weeks' => array_keys($matchups)
        ]);

        return $matchups;
    }
}