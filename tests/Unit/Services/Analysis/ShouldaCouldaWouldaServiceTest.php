<?php

use App\Services\Analysis\ShouldaCouldaWouldaService;
use App\Services\Sleeper\LeagueDataService;
use App\Services\Fantasy\ScheduleAnalysisService;
use App\Services\Fantasy\AlternativeRecordsService;
use App\Services\Fantasy\StrengthOfScheduleService;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;
use App\DataTransferObjects\Analysis\AnalysisResults;
use App\DataTransferObjects\League\WeeklySchedule;
use App\Exceptions\SleeperApi\InvalidLeagueException;
use HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Log::shouldReceive('info')->andReturn(null);
    Log::shouldReceive('error')->andReturn(null);
});

it('analyzes league successfully with all services', function () {
    $leagueId = new LeagueId('123456789');

    // Create a proper LeagueData DTO since that's what the interface expects
    $rawLeagueData = (object) ['name' => 'Test League', 'total_rosters' => 2, 'settings' => (object) ['playoff_week_start' => 15]];
    $initialSchedule = new \App\DataTransferObjects\League\WeeklySchedule();
    $managers = [
        1 => ['roster_id' => 1, 'user_id' => 'user1', 'name' => 'Player 1', 'win' => 1, 'loss' => 0],
        2 => ['roster_id' => 2, 'user_id' => 'user2', 'name' => 'Player 2', 'win' => 0, 'loss' => 1]
    ];

    $rawMatchups = [
        1 => [
            (object) ['roster_id' => 1, 'points' => 120.5, 'matchup_id' => 1],
            (object) ['roster_id' => 2, 'points' => 115.3, 'matchup_id' => 1]
        ]
    ];

    $rawUsers = [
        (object) ['user_id' => 'user1', 'display_name' => 'Player 1', 'avatar' => 'avatar1'],
        (object) ['user_id' => 'user2', 'display_name' => 'Player 2', 'avatar' => null]
    ];

    $rawRosters = [
        (object) ['roster_id' => 1, 'owner_id' => 'user1', 'settings' => (object) ['wins' => 1, 'losses' => 0]],
        (object) ['roster_id' => 2, 'owner_id' => 'user2', 'settings' => (object) ['wins' => 0, 'losses' => 1]]
    ];

    $leagueData = new \App\DataTransferObjects\League\LeagueData(
        $leagueId,
        'Test League',
        2,
        new Week(15),
        new Week(3),
        $managers,
        $initialSchedule,
        $rawLeagueData,
        $rawMatchups,
        $rawUsers,
        $rawRosters
    );

    // Mock schedule from ScheduleAnalysisService
    $schedule = new WeeklySchedule();
    $schedule->addMatchup(
        new Week(1),
        new \App\ValueObjects\RosterId(1),
        ['score' => 120.5, 'vs' => 2, 'roster_id' => 1]
    );

    // Mock managers with schedules
    $managersWithSchedules = [
        1 => [
            'roster_id' => 1,
            'name' => 'Player 1',
            'schedule' => [1 => ['score' => 120.5, 'vs' => 2, 'roster_id' => 1]],
            'records' => [
                1 => ['name' => 'Player 1', 'roster_id' => 1, 'win' => 0, 'loss' => 0],
                2 => ['name' => 'Player 2', 'roster_id' => 2, 'win' => 0, 'loss' => 0]
            ]
        ],
        2 => [
            'roster_id' => 2,
            'name' => 'Player 2',
            'schedule' => [1 => ['score' => 115.3, 'vs' => 1, 'roster_id' => 2]],
            'records' => [
                1 => ['name' => 'Player 1', 'roster_id' => 1, 'win' => 0, 'loss' => 0],
                2 => ['name' => 'Player 2', 'roster_id' => 2, 'win' => 0, 'loss' => 0]
            ]
        ]
    ];

    // Mock managers with calculated records
    $managersWithRecords = [
        1 => [
            'roster_id' => 1,
            'name' => 'Player 1',
            'schedule' => [1 => ['score' => 120.5, 'vs' => 2, 'roster_id' => 1]],
            'records' => [
                1 => ['name' => 'Player 1', 'roster_id' => 1, 'win' => 1, 'loss' => 0],
                2 => ['name' => 'Player 2', 'roster_id' => 2, 'win' => 1, 'loss' => 0]
            ]
        ],
        2 => [
            'roster_id' => 2,
            'name' => 'Player 2',
            'schedule' => [1 => ['score' => 115.3, 'vs' => 1, 'roster_id' => 2]],
            'records' => [
                1 => ['name' => 'Player 1', 'roster_id' => 1, 'win' => 0, 'loss' => 1],
                2 => ['name' => 'Player 2', 'roster_id' => 2, 'win' => 0, 'loss' => 1]
            ]
        ]
    ];

    // Mock strength of schedule
    $strengthOfSchedule = [
        'overall_wins' => [1 => 1, 2 => 0],
        'overall_losses' => [2 => 2, 1 => 1], // Sorted by losses desc
        'rankings' => [2, 1]
    ];

    // Mock the LaravelSleeper facade
    LaravelSleeper::shouldReceive('showAvatar')
        ->with('avatar1')
        ->andReturn('https://sleepercdn.com/avatars/thumbs/avatar1');

    // Create mock services with proper return type
    $mockLeagueDataService = Mockery::mock(LeagueDataService::class);
    $mockScheduleAnalysis = Mockery::mock(ScheduleAnalysisService::class);
    $mockRecordsCalculator = Mockery::mock(AlternativeRecordsService::class);
    $mockStrengthCalculator = Mockery::mock(StrengthOfScheduleService::class);

    // Set up expectations
    $mockLeagueDataService->shouldReceive('getCompleteLeagueData')
        ->once()
        ->with($leagueId)
        ->andReturn($leagueData);

    $mockScheduleAnalysis->shouldReceive('buildScheduleFromMatchups')
        ->once()
        ->with($leagueData->rawMatchups)
        ->andReturn($schedule);

    $mockScheduleAnalysis->shouldReceive('addSchedulesToManagers')
        ->once()
        ->andReturn($managersWithSchedules);

    $mockRecordsCalculator->shouldReceive('calculateAlternativeRecords')
        ->once()
        ->with($managersWithSchedules)
        ->andReturn($managersWithRecords);

    $mockStrengthCalculator->shouldReceive('generateStrengthAnalysis')
        ->once()
        ->with($managersWithRecords)
        ->andReturn($strengthOfSchedule);

    // Create service
    $service = new ShouldaCouldaWouldaService(
        $mockLeagueDataService,
        $mockScheduleAnalysis,
        $mockRecordsCalculator,
        $mockStrengthCalculator
    );

    // Execute
    $result = $service->analyzeLeague($leagueId);

    // Assert
    expect($result)->toBeInstanceOf(AnalysisResults::class)
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->managers)->toBe($managersWithRecords)
        ->and($result->strengthOfSchedule)->toBe($strengthOfSchedule['overall_losses'])
        ->and($result->league->rawLeagueData)->toBe($leagueData->rawLeagueData)
        ->and($result->league->currentWeek)->toBe($leagueData->currentWeek);
});

it('handles service exceptions and returns failure result', function () {
    $leagueId = new LeagueId('123456789');

    $mockLeagueDataService = Mockery::mock(LeagueDataService::class);
    $mockScheduleAnalysis = Mockery::mock(ScheduleAnalysisService::class);
    $mockRecordsCalculator = Mockery::mock(AlternativeRecordsService::class);
    $mockStrengthCalculator = Mockery::mock(StrengthOfScheduleService::class);

    $mockLeagueDataService->shouldReceive('getCompleteLeagueData')
        ->once()
        ->with($leagueId)
        ->andThrow(new InvalidLeagueException($leagueId));

    $service = new ShouldaCouldaWouldaService(
        $mockLeagueDataService,
        $mockScheduleAnalysis,
        $mockRecordsCalculator,
        $mockStrengthCalculator
    );

    $result = $service->analyzeLeague($leagueId);

    expect($result)->toBeInstanceOf(AnalysisResults::class)
        ->and($result->isFailure())->toBeTrue()
        ->and($result->getError())->toBe('This is not a valid Sleeper league ID!')
        ->and($result->managers)->toBeEmpty()
        ->and($result->strengthOfSchedule)->toBeEmpty();
});

it('maps different exception types to appropriate error messages', function () {
    $leagueId = new LeagueId('123456789');

    $mockLeagueDataService = Mockery::mock(LeagueDataService::class);
    $mockScheduleAnalysis = Mockery::mock(ScheduleAnalysisService::class);
    $mockRecordsCalculator = Mockery::mock(AlternativeRecordsService::class);
    $mockStrengthCalculator = Mockery::mock(StrengthOfScheduleService::class);

    $service = new ShouldaCouldaWouldaService(
        $mockLeagueDataService,
        $mockScheduleAnalysis,
        $mockRecordsCalculator,
        $mockStrengthCalculator
    );

    // Test InvalidLeagueException
    $mockLeagueDataService->shouldReceive('getCompleteLeagueData')
        ->once()
        ->andThrow(new \App\Exceptions\SleeperApi\InvalidLeagueException($leagueId));

    $result = $service->analyzeLeague($leagueId);
    expect($result->getError())->toBe('This is not a valid Sleeper league ID!');

    // Test InsufficientDataException
    $mockLeagueDataService->shouldReceive('getCompleteLeagueData')
        ->once()
        ->andThrow(new \App\Exceptions\SleeperApi\InsufficientDataException());

    $result = $service->analyzeLeague($leagueId);
    expect($result->getError())->toBe('Could not retrieve your league settings!');

    // Test ApiConnectionException
    $mockLeagueDataService->shouldReceive('getCompleteLeagueData')
        ->once()
        ->andThrow(new \App\Exceptions\SleeperApi\ApiConnectionException());

    $result = $service->analyzeLeague($leagueId);
    expect($result->getError())->toBe('There was an error fetching matchups!');
});

it('builds managers array correctly from rosters and users', function () {
    // Mock the LaravelSleeper facade
    LaravelSleeper::shouldReceive('showAvatar')
        ->with('avatar1')
        ->andReturn('https://sleepercdn.com/avatars/thumbs/avatar1');

    $rosters = [
        (object) ['roster_id' => 1, 'owner_id' => 'user1', 'settings' => (object) ['wins' => 2, 'losses' => 1]],
        (object) ['roster_id' => 2, 'owner_id' => 'user2', 'settings' => (object) ['wins' => 1, 'losses' => 2]]
    ];

    $users = [
        (object) ['user_id' => 'user1', 'display_name' => 'Player 1', 'avatar' => 'avatar1'],
        (object) ['user_id' => 'user2', 'display_name' => 'Player 2', 'metadata' => (object) ['avatar' => 'meta_avatar2']]
    ];

    $mockServices = [
        Mockery::mock(LeagueDataService::class),
        Mockery::mock(ScheduleAnalysisService::class),
        Mockery::mock(AlternativeRecordsService::class),
        Mockery::mock(StrengthOfScheduleService::class)
    ];

    $service = new ShouldaCouldaWouldaService(...$mockServices);

    // Use reflection to test the private method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('buildManagersArray');
    $method->setAccessible(true);

    $result = $method->invoke($service, $rosters, $users);

    expect($result)->toHaveKey(1)
        ->and($result)->toHaveKey(2)
        ->and($result[1]['roster_id'])->toBe(1)
        ->and($result[1]['user_id'])->toBe('user1')
        ->and($result[1]['name'])->toBe('Player 1')
        ->and($result[1]['win'])->toBe(2)
        ->and($result[1]['loss'])->toBe(1)
        ->and($result[2]['name'])->toBe('Player 2');
});