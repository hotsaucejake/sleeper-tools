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

        if($request->has('league'))
        {
            try {
                $league = LaravelSleeper::getLeague($request->league);
                $users = LaravelSleeper::getLeagueUsers($request->league);
                $rosters = LaravelSleeper::getLeagueRosters($request->league);
                if((bool) $league->settings->playoff_week_start && (bool) $users[0] && (bool) $rosters[0])
                {
                    try {
                        for ($i=1; $i < $league->settings->playoff_week_start; $i++) {
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
        }

        foreach($users as $user) {
            $managers[$user->user_id] = [
                'user_id' => $user->user_id,
                'name' => $user->display_name,
                'avatar' => $user->metadata?->avatar ?? ($user->avatar ? LaravelSleeper::showAvatar($user->avatar) : null),
                // 'avatar' => $user->avatar ? LaravelSleeper::showAvatar($user->avatar) : $user->metadata?->avatar ?? null,
            ];
        }

        foreach($rosters as $roster) {
            $managers[$roster->owner_id] = Arr::add($managers[$roster->owner_id], 'roster_id', $roster->roster_id);
            $managers[$roster->owner_id] = Arr::add($managers[$roster->owner_id], 'win', $roster->settings->wins);
            $managers[$roster->owner_id] = Arr::add($managers[$roster->owner_id], 'loss', $roster->settings->losses);
        }

        foreach($matchups as $w => $week) {
            foreach($week as $matchup) {
                $filtered = Arr::where($week, function ($value, $key) use ($matchup) {
                    return $value->matchup_id === $matchup->matchup_id && $value->roster_id !== $matchup->roster_id;
                });
                $schedule[$w][$matchup->roster_id] = ['score' => $matchup->points, 'vs' => current($filtered)->roster_id];
            }
        }

        foreach($managers as $manager) {
            foreach($schedule as $week => $s) {
                $managers[$manager['user_id']]['schedule'][$week] = array_merge($s[$manager['roster_id']], ['user_id' => $manager['user_id']]);
            }

            foreach($managers as $m) {
                $managers[$manager['user_id']]['records'][$m['roster_id']] = ['name' => $m['name'], 'user_id' => $m['user_id'], 'win' => 0, 'loss' => 0];
            }
        }

        foreach($managers as $manager) {
            foreach($manager['records'] as $roster => $record) { // compare each schedule here
                foreach($managers[$record['user_id']]['schedule'] as $week => $results) { // this is the schedule of the one we're comparing to
                    if($results['vs'] === $manager['roster_id']) { // we're playing ourselves, let's compare the score of the one's whose list this is
                        if($manager['schedule'][$week]['score'] > $managers[$results['user_id']]['schedule'][$week]['score']) {
                            $managers[$manager['user_id']]['records'][$roster]['win']++;
                        } else {
                            $managers[$manager['user_id']]['records'][$roster]['loss']++;
                        }
                    } else { // let's compare our score to $results['score']
                        if($manager['schedule'][$week]['score'] > $results['score']) {
                            $managers[$manager['user_id']]['records'][$roster]['win']++;
                        } else {
                            $managers[$manager['user_id']]['records'][$roster]['loss']++;
                        }
                    }
                }
            }
        }

        $valid_league = true;

        return view('shoulda-coulda-woulda.league-select', compact('valid_league', 'managers'));
    }
}
