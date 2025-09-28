<?php

namespace App\Services\Analysis;

use App\DataTransferObjects\Analysis\Award;
use App\DataTransferObjects\Analysis\PerformanceAwardsResults;
use App\Services\Analysis\Contracts\PerformanceAwardsInterface;
use App\Services\Sleeper\LeagueDataService;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;
use Exception;
use HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper;
use Illuminate\Support\Arr;

class PerformanceAwardsService implements PerformanceAwardsInterface
{
    private mixed $playerData = null;

    public function __construct(
        private LeagueDataService $leagueDataService
    ) {}

    public function analyzeWeeklyPerformance(LeagueId $leagueId, Week $week): PerformanceAwardsResults
    {
        try {
            // Get league data
            $leagueData = $this->leagueDataService->getCompleteLeagueData($leagueId);

            // Load player data for position calculations
            $this->loadPlayerData();

            // Get matchups for the specific week
            $matchups = LaravelSleeper::getLeagueMatchups($leagueId->toString(), $week->toInt());
            if (empty($matchups)) {
                return PerformanceAwardsResults::failure('No matchup data available for week ' . $week->toInt());
            }

            // Build managers array
            $managers = $this->buildManagersArray($leagueData->rawRosters, $leagueData->rawUsers);

            // Analyze performance and generate awards
            $awards = $this->generateAwards($matchups, $managers, $leagueData);

            return PerformanceAwardsResults::success(
                awards: $awards,
                league: $leagueData,
                week: $week->toInt()
            );

        } catch (Exception $e) {
            return PerformanceAwardsResults::failure('Error analyzing performance: ' . $e->getMessage());
        }
    }

    private function buildManagersArray(array $rosters, array $users): array
    {
        $managers = [];

        // Build initial managers array
        foreach ($rosters as $roster) {
            $managers[$roster->roster_id] = [
                'roster_id' => $roster->roster_id,
                'user_id' => $roster->owner_id,
            ];
        }

        // Add user details
        foreach ($users as $user) {
            foreach ($managers as $manager) {
                if ($manager['user_id'] === $user->user_id) {
                    $managers[$manager['roster_id']] = Arr::add($managers[$manager['roster_id']], 'name', $user->display_name);
                    $managers[$manager['roster_id']] = Arr::add($managers[$manager['roster_id']], 'avatar', $user->metadata?->avatar ?? ($user->avatar ? LaravelSleeper::showAvatar($user->avatar) : null));
                    break;
                }
            }
        }

        return $managers;
    }

    private function loadPlayerData(): void
    {
        if ($this->playerData === null) {
            // Cache player data for 7 days (as recommended by Sleeper API docs)
            $this->playerData = cache()->remember('sleeper_players_nfl', now()->addDays(7), function () {
                return LaravelSleeper::getAllPlayers('nfl');
            });
        }
    }

    private function generateAwards(array $matchups, array $managers, $leagueData): array
    {
        $awards = [];
        $teamScores = [];
        $lineupEfficiencies = [];
        $blowouts = [];

        // Analyze each matchup
        foreach ($matchups as $matchup) {
            $rosterId = $matchup->roster_id;
            $teamScore = $matchup->points ?? 0;
            $teamScores[$rosterId] = $teamScore;

            // Calculate lineup efficiency (actual vs optimal)
            $lineupEfficiencies[$rosterId] = $this->calculateLineupEfficiency($matchup);

            // Store matchup data for blowout analysis
            if (isset($matchup->matchup_id)) {
                $blowouts[$matchup->matchup_id][$rosterId] = $teamScore;
            }
        }

        // Generate specific awards
        $awards = array_merge($awards, $this->generateSpecialAwards($teamScores, $managers));
        $awards = array_merge($awards, $this->generateRonJeremyAward($matchups, $managers));
        $awards = array_merge($awards, $this->generateLineupEfficiencyAwards($lineupEfficiencies, $managers));
        $awards = array_merge($awards, $this->generateBlowoutAwards($blowouts, $managers));
        $awards = array_merge($awards, $this->generateProjectionAwards($matchups, $managers));
        $awards = array_merge($awards, $this->generatePositionAwards($matchups, $managers));
        $awards = array_merge($awards, $this->generateBenchwarmerAwards($matchups, $managers));


        return $awards;
    }

    private function calculateLineupEfficiency($matchup): float
    {
        $actualScore = $matchup->points ?? 0;
        $optimalScore = $this->calculateOptimalLineupScore($matchup);

        return $optimalScore > 0 ? ($actualScore / $optimalScore) * 100 : 0;
    }

    private function calculateOptimalLineupScore($matchup): float
    {
        $players = $matchup->players ?? [];
        $playersPoints = $matchup->players_points ?? (object)[];
        $starters = $matchup->starters ?? [];

        // Get roster settings to understand position requirements
        $rosterSettings = $this->getDefaultRosterSettings(); // Standard Sleeper settings

        // Group players by position with their points
        $playersByPosition = [];
        foreach ($players as $playerId) {
            $points = $playersPoints->{$playerId} ?? 0;
            $position = $this->getPlayerPosition($playerId);

            if (!isset($playersByPosition[$position])) {
                $playersByPosition[$position] = [];
            }

            $playersByPosition[$position][] = [
                'id' => $playerId,
                'points' => $points,
                'position' => $position
            ];
        }

        // Sort each position group by points (highest first)
        foreach ($playersByPosition as $position => $positionPlayers) {
            usort($playersByPosition[$position], fn($a, $b) => $b['points'] <=> $a['points']);
        }

        return $this->calculateOptimalLineup($playersByPosition, $rosterSettings);
    }

    private function getDefaultRosterSettings(): array
    {
        // Standard Sleeper roster settings for most leagues
        return [
            'QB' => 1,
            'RB' => 2,
            'WR' => 2,
            'TE' => 1,
            'FLEX' => 1, // RB/WR/TE
            'K' => 1,
            'DEF' => 1,
        ];
    }

    private function calculateOptimalLineup(array $playersByPosition, array $rosterSettings): float
    {
        $optimalLineup = [];
        $totalPoints = 0;

        // Fill required positions first
        foreach ($rosterSettings as $position => $count) {
            if ($position === 'FLEX') continue; // Handle flex separately

            $availablePlayers = $playersByPosition[$position] ?? [];
            for ($i = 0; $i < $count && $i < count($availablePlayers); $i++) {
                $player = $availablePlayers[$i];
                $optimalLineup[] = $player;
                $totalPoints += $player['points'];

                // Remove from available pool
                $playersByPosition[$position] = array_slice($playersByPosition[$position], 1);
            }
        }

        // Handle FLEX position (best remaining RB/WR/TE)
        if (isset($rosterSettings['FLEX'])) {
            $flexCandidates = [];

            foreach (['RB', 'WR', 'TE'] as $flexPosition) {
                if (isset($playersByPosition[$flexPosition])) {
                    $flexCandidates = array_merge($flexCandidates, $playersByPosition[$flexPosition]);
                }
            }

            // Sort all flex candidates by points
            usort($flexCandidates, fn($a, $b) => $b['points'] <=> $a['points']);

            // Take the best flex player
            for ($i = 0; $i < $rosterSettings['FLEX'] && $i < count($flexCandidates); $i++) {
                $player = $flexCandidates[$i];
                $optimalLineup[] = $player;
                $totalPoints += $player['points'];
            }
        }

        return $totalPoints;
    }

    private function generateSpecialAwards(array $teamScores, array $managers): array
    {
        $awards = [];

        if (empty($teamScores)) {
            return $awards;
        }

        // Money Shot (highest scoring team)
        $highestScoringTeam = array_keys($teamScores, max($teamScores))[0];
        $awards[] = new Award(
            title: 'The Money Shot',
            emoji: '💰',
            managerName: $managers[$highestScoringTeam]['name'],
            description: 'Highest scoring team for the week',
            value: $teamScores[$highestScoringTeam]
        );

        // The Taco (lowest scoring team)
        $lowestScoringTeam = array_keys($teamScores, min($teamScores))[0];
        $awards[] = new Award(
            title: 'The Taco',
            emoji: '🌮',
            managerName: $managers[$lowestScoringTeam]['name'],
            description: 'Lowest scoring team for the week',
            value: $teamScores[$lowestScoringTeam]
        );

        return $awards;
    }

    private function generateLineupEfficiencyAwards(array $lineupEfficiencies, array $managers): array
    {
        $awards = [];

        if (empty($lineupEfficiencies)) {
            return $awards;
        }

        // Best Manager
        $bestManagerRoster = array_keys($lineupEfficiencies, max($lineupEfficiencies))[0];
        $awards[] = new Award(
            title: 'Best Manager',
            emoji: '🔥',
            managerName: $managers[$bestManagerRoster]['name'],
            description: sprintf('Set a lineup that was %.1f%% of their perfect possible lineup', $lineupEfficiencies[$bestManagerRoster]),
            value: $lineupEfficiencies[$bestManagerRoster]
        );

        // Worst Manager
        $worstManagerRoster = array_keys($lineupEfficiencies, min($lineupEfficiencies))[0];
        $awards[] = new Award(
            title: 'Worst Manager',
            emoji: '🤔',
            managerName: $managers[$worstManagerRoster]['name'],
            description: sprintf('Set the worst lineup and only scored %.1f%% of their perfect possible lineup', $lineupEfficiencies[$worstManagerRoster]),
            value: $lineupEfficiencies[$worstManagerRoster]
        );

        return $awards;
    }

    private function generateBlowoutAwards(array $blowouts, array $managers): array
    {
        $awards = [];
        $biggestMargin = 0;
        $narrowestMargin = PHP_FLOAT_MAX;
        $biggestBlowout = null;
        $narrowestVictory = null;

        foreach ($blowouts as $matchupId => $teams) {
            if (count($teams) !== 2) continue;

            $scores = array_values($teams);
            $rosters = array_keys($teams);

            $winnerScore = max($scores);
            $loserScore = min($scores);
            $margin = $winnerScore - $loserScore;
            $marginPercent = $loserScore > 0 ? ($margin / $loserScore) * 100 : 100;

            $winnerRoster = array_search($winnerScore, $teams);
            $loserRoster = array_search($loserScore, $teams);

            // Track biggest blowout
            if ($marginPercent > $biggestMargin) {
                $biggestMargin = $marginPercent;
                $biggestBlowout = [
                    'winner' => $winnerRoster,
                    'loser' => $loserRoster,
                    'margin' => $marginPercent
                ];
            }

            // Track narrowest victory
            if ($marginPercent < $narrowestMargin && $margin > 0) {
                $narrowestMargin = $marginPercent;
                $narrowestVictory = [
                    'winner' => $winnerRoster,
                    'loser' => $loserRoster,
                    'margin' => $marginPercent
                ];
            }
        }

        if ($biggestBlowout) {
            $awards[] = new Award(
                title: 'Biggest Blowout',
                emoji: '😂',
                managerName: $managers[$biggestBlowout['winner']]['name'],
                description: sprintf('Beat %s by a margin of %.1f%%!', $managers[$biggestBlowout['loser']]['name'], $biggestBlowout['margin']),
                value: $biggestBlowout['margin'],
                secondaryManagerName: $managers[$biggestBlowout['loser']]['name']
            );
        }

        if ($narrowestVictory) {
            $awards[] = new Award(
                title: 'Narrow Victory',
                emoji: '😱',
                managerName: $managers[$narrowestVictory['winner']]['name'],
                description: sprintf('Beat %s by a margin of %.1f%%!', $managers[$narrowestVictory['loser']]['name'], $narrowestVictory['margin']),
                value: $narrowestVictory['margin'],
                secondaryManagerName: $managers[$narrowestVictory['loser']]['name']
            );
        }

        return $awards;
    }

    private function generatePositionAwards(array $matchups, array $managers): array
    {
        $awards = [];
        $positionBest = [];

        // Analyze each player's performance by position
        foreach ($matchups as $matchup) {
            $rosterId = $matchup->roster_id;
            $starters = $matchup->starters ?? [];
            $startersPoints = $matchup->starters_points ?? [];

            // Map starters to their points
            foreach ($starters as $index => $playerId) {
                $points = $startersPoints[$index] ?? 0;
                $position = $this->getPlayerPosition($playerId); // Would need player data

                if (!isset($positionBest[$position]) || $points > $positionBest[$position]['points']) {
                    $positionBest[$position] = [
                        'player_id' => $playerId,
                        'points' => $points,
                        'roster_id' => $rosterId
                    ];
                }
            }
        }

        // Generate position awards
        $positions = ['QB', 'RB', 'WR', 'TE', 'K', 'DEF'];
        foreach ($positions as $position) {
            if (isset($positionBest[$position])) {
                $rosterId = $positionBest[$position]['roster_id'];
                $playerId = $positionBest[$position]['player_id'];
                $playerInfo = $this->getPlayerInfo($playerId);

                $awards[] = new Award(
                    title: "{$position} of the Week",
                    emoji: '⭐',
                    managerName: $managers[$rosterId]['name'],
                    description: "Started the best {$position} of this week!",
                    value: $positionBest[$position]['points'],
                    playerInfo: $playerInfo
                );
            }
        }

        return $awards;
    }

    private function generateProjectionAwards(array $matchups, array $managers): array
    {
        $awards = [];
        $projectionPerformances = [];

        foreach ($matchups as $matchup) {
            $rosterId = $matchup->roster_id;
            $actualPoints = $matchup->points ?? 0;
            $projectedPoints = $matchup->custom_points ?? $actualPoints; // Using custom_points as projection

            if ($projectedPoints > 0) {
                $performancePercent = (($actualPoints - $projectedPoints) / $projectedPoints) * 100;
                $projectionPerformances[$rosterId] = [
                    'actual' => $actualPoints,
                    'projected' => $projectedPoints,
                    'percent' => $performancePercent
                ];
            }
        }

        if (!empty($projectionPerformances)) {
            // Find best overachiever
            $bestOverachiever = collect($projectionPerformances)
                ->filter(fn($perf) => $perf['percent'] > 0)
                ->sortByDesc('percent')
                ->first();

            if ($bestOverachiever) {
                $rosterId = collect($projectionPerformances)->search($bestOverachiever);
                $awards[] = new Award(
                    title: 'Overachiever',
                    emoji: '🤓',
                    managerName: $managers[$rosterId]['name'] ?? 'Unknown Manager',
                    description: sprintf('Overachieved their projection (%.2f) by %.1f%%!',
                        $bestOverachiever['projected'],
                        abs($bestOverachiever['percent'])
                    ),
                    value: abs($bestOverachiever['percent'])
                );
            }

            // Find worst underachiever
            $worstUnderachiever = collect($projectionPerformances)
                ->filter(fn($perf) => $perf['percent'] < 0)
                ->sortBy('percent')
                ->first();

            if ($worstUnderachiever) {
                $rosterId = collect($projectionPerformances)->search($worstUnderachiever);
                $awards[] = new Award(
                    title: 'Below Expectation',
                    emoji: '💀',
                    managerName: $managers[$rosterId]['name'] ?? 'Unknown Manager',
                    description: sprintf('Missed their projection (%.2f) by %.1f%%!',
                        $worstUnderachiever['projected'],
                        abs($worstUnderachiever['percent'])
                    ),
                    value: abs($worstUnderachiever['percent'])
                );
            }
        }

        return $awards;
    }

    private function generateBenchwarmerAwards(array $matchups, array $managers): array
    {
        $awards = [];
        $benchBest = [];

        foreach ($matchups as $matchup) {
            $rosterId = $matchup->roster_id;
            $starters = $matchup->starters ?? [];
            $players = $matchup->players ?? [];
            $playersPoints = $matchup->players_points ?? (object)[];

            // Find bench players (players not in starters)
            $benchPlayers = array_diff($players, $starters);

            foreach ($benchPlayers as $playerId) {
                $points = $playersPoints->{$playerId} ?? 0;
                $position = $this->getPlayerPosition($playerId);

                if (!isset($benchBest[$position]) || $points > $benchBest[$position]['points']) {
                    $benchBest[$position] = [
                        'player_id' => $playerId,
                        'points' => $points,
                        'roster_id' => $rosterId
                    ];
                }
            }
        }

        // Generate benchwarmer awards for main positions
        $positions = ['QB', 'RB', 'WR', 'TE'];
        foreach ($positions as $position) {
            if (isset($benchBest[$position]) && $benchBest[$position]['points'] > 0) {
                $rosterId = $benchBest[$position]['roster_id'];
                $playerId = $benchBest[$position]['player_id'];
                $playerInfo = $this->getPlayerInfo($playerId);

                $awards[] = new Award(
                    title: "{$position} Benchwarmer of the Week",
                    emoji: '👀',
                    managerName: $managers[$rosterId]['name'] ?? 'Unknown Manager',
                    description: "Had the best {$position} benchwarmer this week!",
                    value: $benchBest[$position]['points'],
                    playerInfo: $playerInfo
                );
            }
        }

        return $awards;
    }

    private function generateRonJeremyAward(array $matchups, array $managers): array
    {
        $awards = [];
        $highestPlayerScore = 0;
        $ronJeremyWinner = null;
        $ronJeremyPlayerId = null;

        foreach ($matchups as $matchup) {
            $rosterId = $matchup->roster_id;
            $starters = $matchup->starters ?? [];
            $startersPoints = $matchup->starters_points ?? [];

            // Find highest scoring starter on this team
            foreach ($starters as $index => $playerId) {
                $points = $startersPoints[$index] ?? 0;
                if ($points > $highestPlayerScore) {
                    $highestPlayerScore = $points;
                    $ronJeremyWinner = $rosterId;
                    $ronJeremyPlayerId = $playerId;
                }
            }
        }

        if ($ronJeremyWinner && $highestPlayerScore > 0 && $ronJeremyPlayerId) {
            $playerInfo = $this->getPlayerInfo($ronJeremyPlayerId);

            $awards[] = new Award(
                title: 'The Ron Jeremy Performance Award',
                emoji: '🍆',
                managerName: $managers[$ronJeremyWinner]['name'] ?? 'Unknown Manager',
                description: 'Had the highest scoring individual player this week!',
                value: $highestPlayerScore,
                playerInfo: $playerInfo
            );
        }

        return $awards;
    }

    private function getPlayerPosition(string $playerId): string
    {
        // Handle team defenses (they use team abbreviations as IDs)
        if (strlen($playerId) <= 4 && ctype_alpha($playerId)) {
            return 'DEF';
        }

        // Look up player in Sleeper player data
        if (isset($this->playerData->{$playerId})) {
            $player = $this->playerData->{$playerId};

            // Handle player object or array format
            $position = is_object($player) ? ($player->position ?? 'Unknown') : ($player['position'] ?? 'Unknown');

            // Map position variations to standard positions
            return match($position) {
                'QB' => 'QB',
                'RB' => 'RB',
                'WR' => 'WR',
                'TE' => 'TE',
                'K' => 'K',
                'DEF' => 'DEF',
                default => 'RB' // Default fallback for unknown positions
            };
        }

        // Fallback for unknown players
        return 'RB';
    }

    private function getPlayerInfo(string $playerId): array
    {
        // Handle team defenses
        if (strlen($playerId) <= 4 && ctype_alpha($playerId)) {
            return [
                'name' => strtoupper($playerId) . ' DEF',
                'position' => 'DEF',
                'team' => strtoupper($playerId),
                'avatar' => null
            ];
        }

        // Look up player in Sleeper player data
        if (isset($this->playerData->{$playerId})) {
            $player = $this->playerData->{$playerId};

            $firstName = is_object($player) ? ($player->first_name ?? '') : ($player['first_name'] ?? '');
            $lastName = is_object($player) ? ($player->last_name ?? '') : ($player['last_name'] ?? '');
            $position = is_object($player) ? ($player->position ?? 'Unknown') : ($player['position'] ?? 'Unknown');
            $team = is_object($player) ? ($player->team ?? '') : ($player['team'] ?? '');

            // Use Sleeper's actual avatar pattern
            $avatarUrl = null;
            if (!empty($playerId) && is_numeric($playerId)) {
                $avatarUrl = "https://sleepercdn.com/content/nfl/players/thumb/{$playerId}.jpg";
            }

            return [
                'name' => trim($firstName . ' ' . $lastName) ?: 'Unknown Player',
                'position' => $position,
                'team' => $team,
                'avatar' => $avatarUrl
            ];
        }

        return [
            'name' => 'Unknown Player',
            'position' => 'RB',
            'team' => '',
            'avatar' => null
        ];
    }
}
