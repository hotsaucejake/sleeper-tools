<?php

use App\Services\Sleeper\SleeperApiService;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;
use App\Exceptions\SleeperApi\InvalidLeagueException;
use App\Exceptions\SleeperApi\ApiConnectionException;
use HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Log::shouldReceive('debug')->andReturn(null);
    Log::shouldReceive('error')->andReturn(null);
    Log::shouldReceive('warning')->andReturn(null);
});

it('fetches league data successfully', function () {
    $leagueId = new LeagueId('123456789');
    $expectedLeague = (object) ['name' => 'Test League', 'total_rosters' => 10];

    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->with('123456789')
        ->andReturn($expectedLeague);

    $service = new SleeperApiService();
    $result = $service->getLeague($leagueId);

    expect($result)->toBe($expectedLeague);
});

it('throws InvalidLeagueException when league fetch fails', function () {
    $leagueId = new LeagueId('123456789');

    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->with('123456789')
        ->andThrow(new Exception('League not found'));

    $service = new SleeperApiService();

    expect(fn() => $service->getLeague($leagueId))->toThrow(InvalidLeagueException::class);
});

it('fetches league users successfully', function () {
    $leagueId = new LeagueId('123456789');
    $expectedUsers = [
        (object) ['user_id' => '1', 'display_name' => 'User 1'],
        (object) ['user_id' => '2', 'display_name' => 'User 2']
    ];

    LaravelSleeper::shouldReceive('getLeagueUsers')
        ->once()
        ->with('123456789')
        ->andReturn($expectedUsers);

    $service = new SleeperApiService();
    $result = $service->getLeagueUsers($leagueId);

    expect($result)->toBe($expectedUsers);
});

it('throws ApiConnectionException when users fetch fails', function () {
    $leagueId = new LeagueId('123456789');

    LaravelSleeper::shouldReceive('getLeagueUsers')
        ->once()
        ->with('123456789')
        ->andThrow(new Exception('Connection failed'));

    $service = new SleeperApiService();

    expect(fn() => $service->getLeagueUsers($leagueId))->toThrow(ApiConnectionException::class);
});

it('fetches league rosters successfully', function () {
    $leagueId = new LeagueId('123456789');
    $expectedRosters = [
        (object) ['roster_id' => 1, 'owner_id' => 'user1'],
        (object) ['roster_id' => 2, 'owner_id' => 'user2']
    ];

    LaravelSleeper::shouldReceive('getLeagueRosters')
        ->once()
        ->with('123456789')
        ->andReturn($expectedRosters);

    $service = new SleeperApiService();
    $result = $service->getLeagueRosters($leagueId);

    expect($result)->toBe($expectedRosters);
});

it('fetches league matchups successfully', function () {
    $leagueId = new LeagueId('123456789');
    $week = new Week(1);
    $expectedMatchups = [
        (object) ['roster_id' => 1, 'points' => 120.5, 'matchup_id' => 1],
        (object) ['roster_id' => 2, 'points' => 115.3, 'matchup_id' => 1]
    ];

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->once()
        ->with('123456789', 1)
        ->andReturn($expectedMatchups);

    $service = new SleeperApiService();
    $result = $service->getLeagueMatchups($leagueId, $week);

    expect($result)->toBe($expectedMatchups);
});

it('fetches sport state successfully', function () {
    $expectedState = (object) ['week' => 8, 'season' => '2024'];

    LaravelSleeper::shouldReceive('getSportState')
        ->once()
        ->andReturn($expectedState);

    $service = new SleeperApiService();
    $result = $service->getSportState();

    expect($result)->toBe($expectedState);
});

it('generates avatar URL successfully', function () {
    $avatarId = 'abc123';
    $expectedUrl = 'https://sleepercdn.com/avatars/thumbs/abc123';

    LaravelSleeper::shouldReceive('showAvatar')
        ->once()
        ->with('abc123')
        ->andReturn($expectedUrl);

    $service = new SleeperApiService();
    $result = $service->showAvatar($avatarId);

    expect($result)->toBe($expectedUrl);
});

it('handles avatar generation failure gracefully', function () {
    $avatarId = 'invalid';

    LaravelSleeper::shouldReceive('showAvatar')
        ->once()
        ->with('invalid')
        ->andThrow(new Exception('Avatar not found'));

    $service = new SleeperApiService();
    $result = $service->showAvatar($avatarId);

    // Should return fallback URL instead of throwing
    expect($result)->toBe('https://sleepercdn.com/avatars/thumbs/invalid');
});