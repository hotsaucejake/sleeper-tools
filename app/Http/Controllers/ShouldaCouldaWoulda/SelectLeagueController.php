<?php

namespace App\Http\Controllers\ShouldaCouldaWoulda;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelectLeagueRequest;
use App\Services\Analysis\Contracts\FantasyAnalysisInterface;
use App\ValueObjects\LeagueId;
use InvalidArgumentException;

class SelectLeagueController extends Controller
{
    public function __construct(
        private FantasyAnalysisInterface $analysisService
    ) {}

    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(SelectLeagueRequest $request)
    {
        if (! $request->has('league')) {
            return view('shoulda-coulda-woulda.league-select', [
                'valid_league' => false,
                'managers' => [],
                'overall_losses' => [],
                'league' => null,
                'current_week' => null,
            ]);
        }

        try {
            $leagueId = new LeagueId($request->league);
            $results = $this->analysisService->analyzeLeague($leagueId);

            if ($results->isFailure()) {
                return redirect()
                    ->route('shoulda-coulda-woulda.select-league')
                    ->with('error', $results->getError());
            }

            return view('shoulda-coulda-woulda.league-select', [
                'valid_league' => true,
                'managers' => $results->managers,
                'overall_losses' => $results->strengthOfSchedule,
                'league' => $results->league->rawLeagueData,
                'current_week' => $results->league->currentWeek,
            ]);

        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('shoulda-coulda-woulda.select-league')
                ->with('error', 'Invalid league ID provided');
        }
    }
}
