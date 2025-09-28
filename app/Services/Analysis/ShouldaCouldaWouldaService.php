<?php

namespace App\Services\Analysis;

use App\DataTransferObjects\Analysis\AnalysisResults;
use App\Services\Analysis\Contracts\FantasyAnalysisInterface;
use App\Services\Fantasy\AlternativeRecordsService;
use App\Services\Fantasy\ScheduleAnalysisService;
use App\Services\Fantasy\StrengthOfScheduleService;
use App\Services\Sleeper\LeagueDataService;
use App\ValueObjects\LeagueId;
use Exception;
use HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper;
use Illuminate\Support\Arr;

class ShouldaCouldaWouldaService implements FantasyAnalysisInterface
{
    public function __construct(
        private LeagueDataService $leagueDataService,
        private ScheduleAnalysisService $scheduleAnalysis,
        private AlternativeRecordsService $recordsCalculator,
        private StrengthOfScheduleService $strengthCalculator
    ) {}

    public function analyzeLeague(LeagueId $leagueId): AnalysisResults
    {
        try {
            // Phase 1: Get complete league data
            $leagueData = $this->leagueDataService->getCompleteLeagueData($leagueId);

            // Phase 2: Build schedules
            $schedule = $this->scheduleAnalysis->buildScheduleFromMatchups($leagueData->rawMatchups);
            $managers = $this->buildManagersArray($leagueData->rawRosters, $leagueData->rawUsers);
            $managersWithSchedules = $this->scheduleAnalysis->addSchedulesToManagers($managers, $schedule);

            // Phase 3: Calculate alternative records
            $managersWithRecords = $this->recordsCalculator->calculateAlternativeRecords($managersWithSchedules);

            // Phase 4: Calculate strength of schedule
            $strengthOfSchedule = $this->strengthCalculator->generateStrengthAnalysis($managersWithRecords);

            if (! ($leagueData instanceof \App\DataTransferObjects\League\LeagueData)) {
                throw new \Exception('Expected LeagueData, got '.get_class($leagueData));
            }

            return AnalysisResults::success(
                managers: $managersWithRecords,
                strengthOfSchedule: $strengthOfSchedule['overall_losses'],
                league: $leagueData
            );

        } catch (Exception $e) {
            return AnalysisResults::failure($this->getErrorMessage($e));
        }
    }

    private function getErrorMessage(Exception $e): string
    {
        // Map specific exceptions to user-friendly messages
        $className = get_class($e);

        return match ($className) {
            'App\Exceptions\SleeperApi\InvalidLeagueException' => 'This is not a valid Sleeper league ID!',
            'App\Exceptions\SleeperApi\InsufficientDataException' => 'Could not retrieve your league settings!',
            'App\Exceptions\SleeperApi\ApiConnectionException' => 'There was an error fetching matchups!',
            default => 'This is not a valid Sleeper league ID!'
        };
    }

    private function buildManagersArray(array $rosters, array $users): array
    {
        $managers = [];

        // Build initial managers array
        foreach ($rosters as $roster) {
            $managers[$roster->roster_id] = [
                'roster_id' => $roster->roster_id,
                'user_id' => $roster->owner_id,
                'win' => $roster->settings->wins,
                'loss' => $roster->settings->losses,
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
}
