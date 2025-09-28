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

            // Get league data to determine current week
            $leagueData = $this->performanceAwardsService->getLeagueData($leagueId);
            $currentWeek = $leagueData->currentWeek->toInt();

            // Default to previous week (current week - 1), minimum week 1
            $defaultWeek = max(1, $currentWeek - 1);
            $selectedWeek = $weekParam ? (int) $weekParam : $defaultWeek;

            $week = new Week($selectedWeek);

            $results = $this->performanceAwardsService->analyzeWeeklyPerformance($leagueId, $week);

            if ($results->isFailure()) {
                return redirect()
                    ->route('dashboard', ['league_id' => $leagueIdParam])
                    ->with('error', $results->getError());
            }

            // Get cumulative award tallies through the selected week
            $awardTallies = $this->performanceAwardsService->calculateAwardTallies($leagueId, $week);

            return view('tools.performance-awards', [
                'awards' => $results->awards,
                'league' => $results->league->rawLeagueData,
                'week' => $results->week,
                'current_week' => $results->league->currentWeek,
                'max_week' => $currentWeek - 1, // Don't show current week in dropdown
                'award_tallies' => $awardTallies,
                'league_id' => $leagueIdParam,
            ]);

        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('dashboard', ['league_id' => $leagueIdParam])
                ->with('error', 'Invalid league ID or week provided');
        }
    }
}