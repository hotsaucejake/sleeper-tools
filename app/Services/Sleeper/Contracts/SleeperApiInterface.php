<?php

namespace App\Services\Sleeper\Contracts;

use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;

interface SleeperApiInterface
{
    /**
     * Get league information
     */
    public function getLeague(LeagueId $leagueId): object;

    /**
     * Get all users in a league
     */
    public function getLeagueUsers(LeagueId $leagueId): array;

    /**
     * Get all rosters in a league
     */
    public function getLeagueRosters(LeagueId $leagueId): array;

    /**
     * Get matchups for a specific week
     */
    public function getLeagueMatchups(LeagueId $leagueId, Week $week): array;

    /**
     * Get current sport state (week, season, etc.)
     */
    public function getSportState(): object;

    /**
     * Generate avatar URL
     */
    public function showAvatar(string $avatarId): string;
}
