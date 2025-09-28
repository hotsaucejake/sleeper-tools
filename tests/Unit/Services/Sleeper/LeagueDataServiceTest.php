<?php

use App\Exceptions\SleeperApi\InsufficientDataException;
use App\Services\Sleeper\Contracts\SleeperApiInterface;
use App\Services\Sleeper\LeagueDataService;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;

it('fetches complete league data successfully', function () {
    $leagueId = new LeagueId('123456789');

    $mockApi = Mockery::mock(SleeperApiInterface::class);

    $league = (object) [
        'name' => 'Test League',
        'total_rosters' => 2,
        'settings' => (object) ['playoff_week_start' => 15],
    ];

    $users = [
        (object) ['user_id' => 'user1', 'display_name' => 'Player 1', 'avatar' => 'avatar1'],
        (object) ['user_id' => 'user2', 'display_name' => 'Player 2', 'avatar' => null],
    ];

    $rosters = [
        (object) ['roster_id' => 1, 'owner_id' => 'user1', 'settings' => (object) ['wins' => 2, 'losses' => 1]],
        (object) ['roster_id' => 2, 'owner_id' => 'user2', 'settings' => (object) ['wins' => 1, 'losses' => 2]],
    ];

    $state = (object) ['week' => 4, 'season' => '2024'];

    $matchups = [
        (object) ['roster_id' => 1, 'points' => 120.5, 'matchup_id' => 1],
        (object) ['roster_id' => 2, 'points' => 115.3, 'matchup_id' => 1],
    ];

    $mockApi->shouldReceive('getLeague')->once()->with($leagueId)->andReturn($league);
    $mockApi->shouldReceive('getLeagueUsers')->once()->with($leagueId)->andReturn($users);
    $mockApi->shouldReceive('getLeagueRosters')->once()->with($leagueId)->andReturn($rosters);
    $mockApi->shouldReceive('getSportState')->once()->andReturn($state);

    // Should fetch matchups for weeks 1, 2, 3 (current_week - 1)
    $mockApi->shouldReceive('getLeagueMatchups')->times(3)->andReturn($matchups);

    $service = new LeagueDataService($mockApi);
    $result = $service->getCompleteLeagueData($leagueId);

    expect($result)->toBeInstanceOf(\App\DataTransferObjects\League\LeagueData::class)
        ->and($result->id)->toBe($leagueId)
        ->and($result->rawLeagueData)->toBe($league)
        ->and($result->rawUsers)->toBe($users)
        ->and($result->rawRosters)->toBe($rosters)
        ->and($result->rawMatchups)->toBeArray()
        ->and($result->currentWeek)->toBeInstanceOf(Week::class)
        ->and($result->currentWeek->toInt())->toBe(4);
});

it('validates league data correctly', function () {
    $mockApi = Mockery::mock(SleeperApiInterface::class);
    $service = new LeagueDataService($mockApi);

    $league = (object) ['settings' => (object) ['playoff_week_start' => 15]];
    $users = [(object) ['user_id' => '1']];
    $rosters = [(object) ['roster_id' => 1]];
    $state = (object) ['week' => 4];

    // Should not throw - validate that the method executes without exception
    $service->validateLeagueData($league, $users, $rosters, $state);
    expect(true)->toBeTrue(); // Test passes if no exception thrown
});

it('throws exception for empty users', function () {
    $mockApi = Mockery::mock(SleeperApiInterface::class);
    $service = new LeagueDataService($mockApi);

    $league = (object) ['settings' => (object) ['playoff_week_start' => 15]];
    $users = []; // Empty users
    $rosters = [(object) ['roster_id' => 1]];
    $state = (object) ['week' => 4];

    expect(fn () => $service->validateLeagueData($league, $users, $rosters, $state))
        ->toThrow(InsufficientDataException::class, 'No users found in league');
});

it('throws exception for empty rosters', function () {
    $mockApi = Mockery::mock(SleeperApiInterface::class);
    $service = new LeagueDataService($mockApi);

    $league = (object) ['settings' => (object) ['playoff_week_start' => 15]];
    $users = [(object) ['user_id' => '1']];
    $rosters = []; // Empty rosters
    $state = (object) ['week' => 4];

    expect(fn () => $service->validateLeagueData($league, $users, $rosters, $state))
        ->toThrow(InsufficientDataException::class, 'No rosters found in league');
});

it('calculates current analysis week correctly', function () {
    $mockApi = Mockery::mock(SleeperApiInterface::class);
    $service = new LeagueDataService($mockApi);

    // Test case where sport week is less than playoff start
    $state = (object) ['week' => 8];
    $playoffStart = 15;

    $result = $service->getCurrentAnalysisWeek($state, $playoffStart);

    expect($result)->toBeInstanceOf(Week::class)
        ->and($result->toInt())->toBe(8);
});

it('uses playoff start when sport week is greater', function () {
    $mockApi = Mockery::mock(SleeperApiInterface::class);
    $service = new LeagueDataService($mockApi);

    // Test case where sport week is greater than playoff start
    $state = (object) ['week' => 18];
    $playoffStart = 15;

    $result = $service->getCurrentAnalysisWeek($state, $playoffStart);

    expect($result)->toBeInstanceOf(Week::class)
        ->and($result->toInt())->toBe(15);
});

it('throws exception for invalid current week', function () {
    $mockApi = Mockery::mock(SleeperApiInterface::class);
    $service = new LeagueDataService($mockApi);

    $league = (object) ['settings' => (object) ['playoff_week_start' => 15]];
    $users = [(object) ['user_id' => '1']];
    $rosters = [(object) ['roster_id' => 1]];
    $state = (object) ['week' => 0]; // Invalid week

    expect(fn () => $service->validateLeagueData($league, $users, $rosters, $state))
        ->toThrow(InsufficientDataException::class, 'Invalid current week calculated');
});
