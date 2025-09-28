<?php

namespace App\Http\Controllers;

use App\ValueObjects\LeagueId;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with available tools for the league.
     */
    public function index(Request $request)
    {
        $leagueIdParam = $request->query('league_id');

        if (!$leagueIdParam) {
            return redirect()->route('landing')->with('error', 'League ID is required.');
        }

        try {
            $leagueId = new LeagueId($leagueIdParam);

            return view('dashboard', [
                'league_id' => $leagueId->toString(),
            ]);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('landing')
                ->with('error', 'Invalid league ID provided. Please check your league ID and try again.');
        }
    }
}