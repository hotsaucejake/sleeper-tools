<?php

namespace App\DataTransferObjects\League;

use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;

class LeagueData
{
    public function __construct(
        public readonly LeagueId $id,
        public readonly string $name,
        public readonly int $totalRosters,
        public readonly Week $playoffWeekStart,
        public readonly Week $currentWeek,
        public readonly array $managers,
        public readonly WeeklySchedule $schedule,
        public readonly object $rawLeagueData,
        public readonly array $rawMatchups = [],
        public readonly array $rawUsers = [],
        public readonly array $rawRosters = []
    ) {}

    public static function fromSleeperData(
        LeagueId $leagueId,
        object $league,
        array $users,
        array $rosters,
        object $sportState,
        array $matchups
    ): self {
        $playoffWeekStart = new Week($league->settings->playoff_week_start);
        $currentWeek = new Week(min($sportState->week, $league->settings->playoff_week_start));

        // Build managers array
        $managers = [];
        foreach ($rosters as $roster) {
            $user = collect($users)->firstWhere('user_id', $roster->owner_id);

            $managers[$roster->roster_id] = [
                'roster_id' => $roster->roster_id,
                'user_id' => $roster->owner_id,
                'win' => $roster->settings->wins,
                'loss' => $roster->settings->losses,
                'name' => $user?->display_name ?? 'Unknown',
                'avatar' => $user?->metadata?->avatar ?? ($user?->avatar ? "https://sleepercdn.com/avatars/thumbs/{$user->avatar}" : null),
            ];
        }

        // Build schedule (just store raw data, let services handle processing)
        $schedule = new WeeklySchedule;

        return new self(
            id: $leagueId,
            name: $league->name,
            totalRosters: $league->total_rosters,
            playoffWeekStart: $playoffWeekStart,
            currentWeek: $currentWeek,
            managers: $managers,
            schedule: $schedule,
            rawLeagueData: $league,
            rawMatchups: $matchups,
            rawUsers: $users,
            rawRosters: $rosters
        );
    }

    public function getManagersCount(): int
    {
        return count($this->managers);
    }

    public function hasLeagueAverageMatch(): bool
    {
        return (bool) ($this->rawLeagueData->settings->league_average_match ?? false);
    }
}
