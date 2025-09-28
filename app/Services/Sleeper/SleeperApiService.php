<?php

namespace App\Services\Sleeper;

use App\Exceptions\SleeperApi\ApiConnectionException;
use App\Exceptions\SleeperApi\InvalidLeagueException;
use App\Services\Sleeper\Contracts\SleeperApiInterface;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;
use Exception;
use HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper;

class SleeperApiService implements SleeperApiInterface
{
    public function getLeague(LeagueId $leagueId): object
    {
        try {
            $league = LaravelSleeper::getLeague($leagueId->toString());

            return $league;
        } catch (Exception $e) {
            throw new InvalidLeagueException($leagueId, $e->getMessage());
        }
    }

    public function getLeagueUsers(LeagueId $leagueId): array
    {
        try {
            $users = LaravelSleeper::getLeagueUsers($leagueId->toString());

            return $users;
        } catch (Exception $e) {
            throw new ApiConnectionException('Failed to fetch league users: '.$e->getMessage(), $e);
        }
    }

    public function getLeagueRosters(LeagueId $leagueId): array
    {
        try {
            $rosters = LaravelSleeper::getLeagueRosters($leagueId->toString());

            return $rosters;
        } catch (Exception $e) {
            throw new ApiConnectionException('Failed to fetch league rosters: '.$e->getMessage(), $e);
        }
    }

    public function getLeagueMatchups(LeagueId $leagueId, Week $week): array
    {
        try {
            $matchups = LaravelSleeper::getLeagueMatchups($leagueId->toString(), $week->toInt());

            return $matchups;
        } catch (Exception $e) {
            throw new ApiConnectionException('Failed to fetch league matchups: '.$e->getMessage(), $e);
        }
    }

    public function getSportState(): object
    {
        try {
            $state = LaravelSleeper::getSportState();

            return $state;
        } catch (Exception $e) {
            throw new ApiConnectionException('Failed to fetch sport state: '.$e->getMessage(), $e);
        }
    }

    public function showAvatar(string $avatarId): string
    {
        try {
            return LaravelSleeper::showAvatar($avatarId);
        } catch (Exception $e) {
            // Return a default avatar URL or null on failure
            return "https://sleepercdn.com/avatars/thumbs/{$avatarId}";
        }
    }
}
