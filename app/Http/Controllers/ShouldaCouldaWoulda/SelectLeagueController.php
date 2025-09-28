<?php

namespace App\Http\Controllers\ShouldaCouldaWoulda;

use App\Http\Controllers\Controller;
use HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SelectLeagueController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        Log::info('=== SHOULDA COULDA WOULDA ANALYSIS STARTED ===');
        Log::info('Request parameters:', $request->all());

        $valid_league = false;
        $managers = [];
        $schedule = [];
        $overall_wins = [];
        $overall_losses = [];
        $league = null;
        $current_week = null;

        Log::info('Initial variables set:', [
            'valid_league' => $valid_league,
            'managers_count' => count($managers),
            'schedule_count' => count($schedule)
        ]);

        if ($request->has('league')) {
            Log::info('League ID provided:', ['league_id' => $request->league]);

            try {
                Log::info('Fetching league data from Sleeper API...');
                $league = LaravelSleeper::getLeague($request->league);
                Log::info('League data retrieved:', [
                    'league_name' => $league->name ?? 'Unknown',
                    'total_rosters' => $league->total_rosters ?? 'Unknown',
                    'playoff_week_start' => $league->settings->playoff_week_start ?? 'Unknown'
                ]);

                $users = LaravelSleeper::getLeagueUsers($request->league);
                Log::info('Users retrieved:', ['user_count' => count($users)]);

                $rosters = LaravelSleeper::getLeagueRosters($request->league);
                Log::info('Rosters retrieved:', ['roster_count' => count($rosters)]);

                $state = LaravelSleeper::getSportState();
                Log::info('Sport state retrieved:', [
                    'current_week' => $state->week ?? 'Unknown',
                    'season' => $state->season ?? 'Unknown'
                ]);

                $current_week = min($state->week, $league->settings->playoff_week_start);
                Log::info('Analysis week range calculated:', [
                    'current_week' => $current_week,
                    'sport_state_week' => $state->week,
                    'playoff_start' => $league->settings->playoff_week_start
                ]);
                if ((bool) $current_week && (bool) $users[0] && (bool) $rosters[0]) {
                    Log::info('Data validation passed, fetching matchups...', [
                        'weeks_to_fetch' => $current_week - 1,
                        'has_users' => !empty($users),
                        'has_rosters' => !empty($rosters)
                    ]);

                    try {
                        for ($i = 1; $i < $current_week; $i++) {
                            Log::debug("Fetching matchups for week {$i}");
                            $matchups[$i] = LaravelSleeper::getLeagueMatchups($request->league, $i);
                            Log::debug("Week {$i} matchups retrieved:", [
                                'matchup_count' => count($matchups[$i]),
                                'sample_matchup' => $matchups[$i][0] ?? 'No matchups'
                            ]);
                        }
                        Log::info('All matchups retrieved successfully:', [
                            'total_weeks' => count($matchups),
                            'weeks' => array_keys($matchups)
                        ]);
                    } catch (\Throwable $th) {
                        Log::error('Error fetching matchups:', [
                            'error' => $th->getMessage(),
                            'trace' => $th->getTraceAsString()
                        ]);
                        return redirect()->route('shoulda-coulda-woulda.select-league')->with('error', 'There was an error fetching matchups!');
                    }

                } else {
                    Log::warning('Data validation failed:', [
                        'current_week' => $current_week,
                        'has_users' => !empty($users),
                        'has_rosters' => !empty($rosters)
                    ]);
                    return redirect()->route('shoulda-coulda-woulda.select-league')->with('error', 'Could not retrieve your league settings!');
                }
            } catch (\Throwable $th) {
                Log::error('Error fetching league data:', [
                    'league_id' => $request->league,
                    'error' => $th->getMessage(),
                    'trace' => $th->getTraceAsString()
                ]);
                return redirect()->route('shoulda-coulda-woulda.select-league')->with('error', 'This is not a valid Sleeper league ID!');
            }

            Log::info('=== PHASE 1: Building managers array from rosters ===');
            foreach ($rosters as $roster) {
                $managers[$roster->roster_id] = [
                    'roster_id' => $roster->roster_id,
                    'user_id' => $roster->owner_id,
                    'win' => $roster->settings->wins,
                    'loss' => $roster->settings->losses,
                ];
                Log::debug("Manager added:", [
                    'roster_id' => $roster->roster_id,
                    'user_id' => $roster->owner_id,
                    'wins' => $roster->settings->wins,
                    'losses' => $roster->settings->losses
                ]);
            }
            Log::info('Managers array built:', ['total_managers' => count($managers)]);

            Log::info('=== PHASE 2: Adding user details to managers ===');
            foreach ($users as $user) {
                Log::debug("Processing user:", [
                    'user_id' => $user->user_id,
                    'display_name' => $user->display_name
                ]);

                foreach ($managers as $manager) {
                    if ($manager['user_id'] === $user->user_id) {
                        $managers[$manager['roster_id']] = Arr::add($managers[$manager['roster_id']], 'name', $user->display_name);
                        $managers[$manager['roster_id']] = Arr::add($managers[$manager['roster_id']], 'avatar', $user->metadata?->avatar ?? ($user->avatar ? LaravelSleeper::showAvatar($user->avatar) : null));

                        Log::debug("User matched to roster:", [
                            'roster_id' => $manager['roster_id'],
                            'user_name' => $user->display_name,
                            'has_avatar' => !empty($user->avatar)
                        ]);
                        break;
                    }
                }
            }
            Log::info('User details added to managers');

            Log::info('=== PHASE 3: Building schedule from matchups ===');
            foreach ($matchups as $w => $week) {
                Log::debug("Processing week {$w}:", ['matchup_count' => count($week)]);

                foreach ($week as $matchup) {
                    $filtered = Arr::where($week, function ($value, $key) use ($matchup) {
                        return $value->matchup_id === $matchup->matchup_id && $value->roster_id !== $matchup->roster_id;
                    });

                    $opponent = current($filtered);
                    $schedule[$w][$matchup->roster_id] = [
                        'score' => $matchup->points,
                        'vs' => $opponent->roster_id,
                        'roster_id' => $matchup->roster_id
                    ];

                    Log::debug("Matchup processed:", [
                        'week' => $w,
                        'roster_id' => $matchup->roster_id,
                        'score' => $matchup->points,
                        'vs' => $opponent->roster_id,
                        'matchup_id' => $matchup->matchup_id
                    ]);
                }
            }
            Log::info('Schedule built:', [
                'total_weeks' => count($schedule),
                'sample_week' => !empty($schedule) ? array_keys($schedule)[0] : 'No schedule'
            ]);

            Log::info('=== PHASE 4: Adding schedules and initializing records for managers ===');
            foreach ($managers as $manager) {
                Log::debug("Processing manager schedules:", [
                    'roster_id' => $manager['roster_id'],
                    'name' => $manager['name'] ?? 'Unknown'
                ]);

                foreach ($schedule as $week => $s) {
                    $managers[$manager['roster_id']]['schedule'][$week] = $s[$manager['roster_id']];
                }

                Log::debug("Schedule added for manager {$manager['roster_id']}:", [
                    'weeks_scheduled' => count($managers[$manager['roster_id']]['schedule'] ?? [])
                ]);

                foreach ($managers as $m) {
                    $managers[$manager['roster_id']]['records'][$m['roster_id']] = [
                        'name' => $m['name'],
                        'roster_id' => $m['roster_id'],
                        'win' => 0,
                        'loss' => 0
                    ];
                }

                Log::debug("Records initialized for manager {$manager['roster_id']}:", [
                    'opponent_count' => count($managers[$manager['roster_id']]['records'])
                ]);
            }
            Log::info('Schedules and records initialized for all managers');

            Log::info('=== PHASE 5: Calculating alternative records (CORE ALGORITHM) ===');
            foreach ($managers as $manager) {
                Log::debug("Calculating alternative records for manager:", [
                    'roster_id' => $manager['roster_id'],
                    'name' => $manager['name'] ?? 'Unknown'
                ]);

                foreach ($manager['records'] as $roster => $record) {
                    Log::debug("  Comparing against schedule of roster {$roster}");

                    foreach ($managers[$roster]['schedule'] as $week => $results) {
                        $manager_score = $manager['schedule'][$week]['score'];

                        if ($results['vs'] === $manager['roster_id']) {
                            // Direct matchup - compare actual scores
                            $opponent_score = $managers[$results['roster_id']]['schedule'][$week]['score'];
                            $win = $manager_score > $opponent_score;

                            Log::debug("    Week {$week} - Direct matchup:", [
                                'manager_score' => $manager_score,
                                'opponent_score' => $opponent_score,
                                'result' => $win ? 'WIN' : 'LOSS'
                            ]);

                            if ($win) {
                                $managers[$manager['roster_id']]['records'][$roster]['win']++;
                            } else {
                                $managers[$manager['roster_id']]['records'][$roster]['loss']++;
                            }
                        } else {
                            // Alternative matchup - compare manager's score vs opponent's opponent
                            $opponent_score = $managers[$results['vs']]['schedule'][$week]['score'];
                            $win = $manager_score > $opponent_score;

                            Log::debug("    Week {$week} - Alternative matchup:", [
                                'manager_score' => $manager_score,
                                'opponent_opponent_score' => $opponent_score,
                                'result' => $win ? 'WIN' : 'LOSS'
                            ]);

                            if ($win) {
                                $managers[$manager['roster_id']]['records'][$roster]['win']++;
                            } else {
                                $managers[$manager['roster_id']]['records'][$roster]['loss']++;
                            }
                        }
                    }
                }

                Log::debug("Alternative records calculated for {$manager['roster_id']}:", [
                    'total_opponents' => count($manager['records'])
                ]);
            }
            Log::info('Alternative records calculation completed');

            Log::info('=== PHASE 6: Calculating overall wins/losses and strength of schedule ===');
            foreach ($managers as $manager) {
                foreach ($manager['records'] as $roster => $record) {
                    $overall_wins[$roster] = 0;
                    $overall_losses[$roster] = 0;
                }
            }
            Log::info('Overall arrays initialized');

            foreach ($managers as $manager) {
                foreach ($manager['records'] as $roster => $record) {
                    $overall_wins[$roster] += $record['win'];
                    $overall_losses[$roster] += $record['loss'];
                }
            }

            Log::info('Overall wins/losses calculated:', [
                'overall_wins' => $overall_wins,
                'overall_losses' => $overall_losses
            ]);

            arsort($overall_losses);
            Log::info('Overall losses sorted (strength of schedule):', array_keys($overall_losses));

            $valid_league = true;
            Log::info('=== ANALYSIS COMPLETED SUCCESSFULLY ===');
        }

        Log::info('Returning view with data:', [
            'valid_league' => $valid_league,
            'managers_count' => count($managers),
            'overall_losses_count' => count($overall_losses),
            'current_week' => $current_week
        ]);

        return view('shoulda-coulda-woulda.league-select', compact('valid_league', 'managers', 'overall_losses', 'league', 'current_week'));
    }
}
