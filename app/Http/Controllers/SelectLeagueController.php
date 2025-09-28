<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelectLeagueRequest;
use App\ValueObjects\LeagueId;
use InvalidArgumentException;

class SelectLeagueController extends Controller
{
    /**
     * Display the league selection page or handle league submission.
     */
    public function __invoke(SelectLeagueRequest $request)
    {
        if (! $request->has('league')) {
            return view('league-select', [
                'valid_league' => false,
                'managers' => [],
                'overall_losses' => [],
                'league' => null,
                'current_week' => null,
            ]);
        }

        try {
            // Validate the league ID format
            new LeagueId($request->league);

            // Redirect to dashboard with valid league ID
            return redirect()->route('dashboard', ['league_id' => $request->league]);

        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('home')
                ->with('error', 'Invalid league ID provided. Please check your league ID and try again.');
        }
    }
}
