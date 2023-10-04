<?php

namespace App\Http\Controllers\ShouldaCouldaWoulda;

use App\Http\Controllers\Controller;
use HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SelectLeagueController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $valid_league = false;
        $managers = [];
        $schedule = [];
        $overall_wins = [];
        $overall_losses = [];
        $league = null;

        if($request->has('league'))
        {
            try {
                $league = LaravelSleeper::getLeague($request->league);
                $users = LaravelSleeper::getLeagueUsers($request->league);
                $rosters = LaravelSleeper::getLeagueRosters($request->league);
                $state = LaravelSleeper::getSportState();
                if((bool) $league->settings->playoff_week_start && (bool) $users[0] && (bool) $rosters[0])
                {
                    try {
                        $weeks = $state->week <= $league->settings->playoff_week_start ? $state->week : $league->settings->playoff_week_start;
                        for ($i=1; $i < $weeks; $i++) {
                            $matchups[$i] = LaravelSleeper::getLeagueMatchups($request->league, $i);
                        }
                    } catch (\Throwable $th) {
                        return redirect()->route('shoulda-coulda-woulda.select-league')->with('error', 'There was an error fetching matchups!');
                    }

                } else {
                    return redirect()->route('shoulda-coulda-woulda.select-league')->with('error', 'Could not retrieve your league settings!');
                }
            } catch (\Throwable $th) {
                return redirect()->route('shoulda-coulda-woulda.select-league')->with('error', 'This is not a valid Sleeper league ID!');
            }

            foreach($rosters as $roster) {
                $managers[$roster->roster_id] = [
                    'roster_id' => $roster->roster_id,
                    'user_id' => $roster->owner_id,
                    'win' => $roster->settings->wins,
                    'loss' => $roster->settings->losses,
                ];
            }

            foreach($users as $user) {
                foreach($managers as $manager) {
                    if($manager['user_id'] === $user->user_id) {
                        $managers[$manager['roster_id']] = Arr::add($managers[$manager['roster_id']], 'name', $user->display_name);
                        $managers[$manager['roster_id']]  = Arr::add($managers[$manager['roster_id']], 'avatar', $user->metadata?->avatar ?? ($user->avatar ? LaravelSleeper::showAvatar($user->avatar) : null));
                    }
                }
            }

            foreach($matchups as $w => $week) {
                foreach($week as $matchup) {
                    $filtered = Arr::where($week, function ($value, $key) use ($matchup) {
                        return $value->matchup_id === $matchup->matchup_id && $value->roster_id !== $matchup->roster_id;
                    });
                    $schedule[$w][$matchup->roster_id] = ['score' => $matchup->points, 'vs' => current($filtered)->roster_id, 'roster_id' => $matchup->roster_id];
                }
            }

            foreach($managers as $manager) {
                foreach($schedule as $week => $s) {
                    // $managers[$manager['roster_id']]['schedule'][$week] = array_merge($s[$manager['roster_id']], ['user_id' => $manager['user_id']]);
                    $managers[$manager['roster_id']]['schedule'][$week] = $s[$manager['roster_id']];
                }

                foreach($managers as $m) {
                    $managers[$manager['roster_id']]['records'][$m['roster_id']] = ['name' => $m['name'], 'roster_id' => $m['roster_id'], 'win' => 0, 'loss' => 0];
                }
            }

            foreach($managers as $manager) {
                foreach($manager['records'] as $roster => $record) { // compare each schedule here
                    foreach($managers[$roster]['schedule'] as $week => $results) { // this is the schedule of the one we're comparing to
                        if($results['vs'] === $manager['roster_id']) { // we're playing ourselves, let's compare the score of the one's whose list this is
                            if($manager['schedule'][$week]['score'] > $managers[$results['roster_id']]['schedule'][$week]['score']) {
                                $managers[$manager['roster_id']]['records'][$roster]['win']++;
                            } else {
                                $managers[$manager['roster_id']]['records'][$roster]['loss']++;
                            }
                        } else { // let's compare our score to $results['score']
                            if($manager['schedule'][$week]['score'] > $managers[$results['vs']]['schedule'][$week]['score']) {
                                $managers[$manager['roster_id']]['records'][$roster]['win']++;
                            } else {
                                $managers[$manager['roster_id']]['records'][$roster]['loss']++;
                            }
                        }
                    }
                }
            }

            foreach($managers as $manager) {
                foreach($manager['records'] as $roster => $record) {
                    $overall_wins[$roster] = 0;
                    $overall_losses[$roster] = 0;
                }
            }

            foreach($managers as $manager) {
                foreach($manager['records'] as $roster => $record) {
                    $overall_wins[$roster] += $record['win'];
                    $overall_losses[$roster] += $record['loss'];
                }
            }

            arsort($overall_losses);

            $valid_league = true;
        }


        return view('shoulda-coulda-woulda.league-select', compact('valid_league', 'managers', 'overall_losses', 'league', 'weeks'));
    }
}
