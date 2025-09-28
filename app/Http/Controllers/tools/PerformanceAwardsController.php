<?php

namespace App\Http\Controllers\tools;

use App\Http\Controllers\Controller;
use App\Services\Analysis\Contracts\PerformanceAwardsInterface;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PerformanceAwardsController extends Controller
{
    public function __construct(
        private PerformanceAwardsInterface $performanceAwardsService
    ) {}

    /**
     * Display the performance awards for a league and week.
     */
    public function __invoke(Request $request)
    {
        $leagueIdParam = $request->query('league_id');
        $weekParam = $request->query('week');

        if (! $leagueIdParam) {
            return redirect()->route('home')->with('error', 'League ID is required.');
        }

        try {
            $leagueId = new LeagueId($leagueIdParam);

            // Default to week 1 if no week specified
            $week = new Week($weekParam ? (int) $weekParam : 1);

            $results = $this->performanceAwardsService->analyzeWeeklyPerformance($leagueId, $week);

            if ($results->isFailure()) {
                return redirect()
                    ->route('dashboard', ['league_id' => $leagueIdParam])
                    ->with('error', $results->getError());
            }

            return view('tools.performance-awards', [
                'awards' => $results->awards,
                'league' => $results->league->rawLeagueData,
                'week' => $results->week,
                'current_week' => $results->league->currentWeek,
                'league_id' => $leagueIdParam,
            ]);

        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('dashboard', ['league_id' => $leagueIdParam])
                ->with('error', 'Invalid league ID or week provided');
        }
    }
}