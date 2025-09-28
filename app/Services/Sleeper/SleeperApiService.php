<?php

namespace App\Services\Sleeper;

use App\Services\Sleeper\Contracts\SleeperApiInterface;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;
use App\Exceptions\SleeperApi\InvalidLeagueException;
use App\Exceptions\SleeperApi\ApiConnectionException;
use HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper;
use Illuminate\Support\Facades\Log;
use Exception;

class SleeperApiService implements SleeperApiInterface
{
    public function getLeague(LeagueId $leagueId): object
    {
        try {
            Log::debug('Fetching league data', ['league_id' => $leagueId->toString()]);

            $league = LaravelSleeper::getLeague($leagueId->toString());

            Log::debug('League data retrieved', [
                'league_name' => $league->name ?? 'Unknown',
                'total_rosters' => $league->total_rosters ?? 'Unknown'
            ]);

            return $league;
        } catch (Exception $e) {
            Log::error('Failed to fetch league data', [
                'league_id' => $leagueId->toString(),
                'error' => $e->getMessage()
            ]);

            throw new InvalidLeagueException($leagueId, $e->getMessage());
        }
    }

    public function getLeagueUsers(LeagueId $leagueId): array
    {
        try {
            Log::debug('Fetching league users', ['league_id' => $leagueId->toString()]);

            $users = LaravelSleeper::getLeagueUsers($leagueId->toString());

            Log::debug('League users retrieved', [
                'user_count' => count($users),
                'league_id' => $leagueId->toString()
            ]);

            return $users;
        } catch (Exception $e) {
            Log::error('Failed to fetch league users', [
                'league_id' => $leagueId->toString(),
                'error' => $e->getMessage()
            ]);

            throw new ApiConnectionException('Failed to fetch league users: ' . $e->getMessage(), $e);
        }
    }

    public function getLeagueRosters(LeagueId $leagueId): array
    {
        try {
            Log::debug('Fetching league rosters', ['league_id' => $leagueId->toString()]);

            $rosters = LaravelSleeper::getLeagueRosters($leagueId->toString());

            Log::debug('League rosters retrieved', [
                'roster_count' => count($rosters),
                'league_id' => $leagueId->toString()
            ]);

            return $rosters;
        } catch (Exception $e) {
            Log::error('Failed to fetch league rosters', [
                'league_id' => $leagueId->toString(),
                'error' => $e->getMessage()
            ]);

            throw new ApiConnectionException('Failed to fetch league rosters: ' . $e->getMessage(), $e);
        }
    }

    public function getLeagueMatchups(LeagueId $leagueId, Week $week): array
    {
        try {
            Log::debug('Fetching league matchups', [
                'league_id' => $leagueId->toString(),
                'week' => $week->toInt()
            ]);

            $matchups = LaravelSleeper::getLeagueMatchups($leagueId->toString(), $week->toInt());

            Log::debug('League matchups retrieved', [
                'matchup_count' => count($matchups),
                'league_id' => $leagueId->toString(),
                'week' => $week->toInt()
            ]);

            return $matchups;
        } catch (Exception $e) {
            Log::error('Failed to fetch league matchups', [
                'league_id' => $leagueId->toString(),
                'week' => $week->toInt(),
                'error' => $e->getMessage()
            ]);

            throw new ApiConnectionException('Failed to fetch league matchups: ' . $e->getMessage(), $e);
        }
    }

    public function getSportState(): object
    {
        try {
            Log::debug('Fetching sport state');

            $state = LaravelSleeper::getSportState();

            Log::debug('Sport state retrieved', [
                'week' => $state->week ?? 'Unknown',
                'season' => $state->season ?? 'Unknown'
            ]);

            return $state;
        } catch (Exception $e) {
            Log::error('Failed to fetch sport state', ['error' => $e->getMessage()]);

            throw new ApiConnectionException('Failed to fetch sport state: ' . $e->getMessage(), $e);
        }
    }

    public function showAvatar(string $avatarId): string
    {
        try {
            return LaravelSleeper::showAvatar($avatarId);
        } catch (Exception $e) {
            Log::warning('Failed to generate avatar URL', [
                'avatar_id' => $avatarId,
                'error' => $e->getMessage()
            ]);

            // Return a default avatar URL or null on failure
            return "https://sleepercdn.com/avatars/thumbs/{$avatarId}";
        }
    }
}