<?php

namespace App\Http\Controllers\tools;

use App\Http\Controllers\Controller;
use App\Services\Analysis\Contracts\FantasyAnalysisInterface;
use App\ValueObjects\LeagueId;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ShouldaCouldaWouldaController extends Controller
{
    public function __construct(
        private FantasyAnalysisInterface $analysisService
    ) {}

    /**
     * Display the shoulda coulda woulda analysis results for a league.
     */
    public function __invoke(Request $request)
    {
        $leagueIdParam = $request->query('league_id');

        if (!$leagueIdParam) {
            return redirect()->route('home')->with('error', 'League ID is required.');
        }

        try {
            $leagueId = new LeagueId($leagueIdParam);
            $results = $this->analysisService->analyzeLeague($leagueId);

            if ($results->isFailure()) {
                return redirect()
                    ->route('dashboard', ['league_id' => $leagueIdParam])
                    ->with('error', $results->getError());
            }

            return view('tools.shoulda-coulda-woulda', [
                'valid_league' => true,
                'managers' => $results->managers,
                'overall_losses' => $results->strengthOfSchedule,
                'league' => $results->league->rawLeagueData,
                'current_week' => $results->league->currentWeek,
                'league_id' => $leagueIdParam,
            ]);

        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('dashboard', ['league_id' => $leagueIdParam])
                ->with('error', 'Invalid league ID provided');
        }
    }
}
