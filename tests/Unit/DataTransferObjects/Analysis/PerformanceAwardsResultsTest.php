<?php

use App\DataTransferObjects\Analysis\Award;
use App\DataTransferObjects\Analysis\PerformanceAwardsResults;
use App\DataTransferObjects\League\LeagueData;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;

it('creates successful performance awards results', function () {
    $awards = [
        new Award('The Money Shot', '💰', 'Manager One', 'Description', 25.5),
        new Award('The Taco', '🌮', 'Manager Two', 'Description', 95.0),
    ];

    $leagueData = createMockLeagueData();

    $result = PerformanceAwardsResults::success($awards, $leagueData, 3);

    expect($result->success)->toBeTrue()
        ->and($result->awards)->toBe($awards)
        ->and($result->league)->toBe($leagueData)
        ->and($result->week)->toBe(3)
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->isFailure())->toBeFalse()
        ->and($result->getError())->toBeNull();
});

it('creates failed performance awards results', function () {
    $errorMessage = 'This is not a valid Sleeper league ID!';

    $result = PerformanceAwardsResults::failure($errorMessage);

    expect($result->success)->toBeFalse()
        ->and($result->awards)->toBe([])
        ->and($result->league)->toBeNull()
        ->and($result->week)->toBeNull()
        ->and($result->isSuccess())->toBeFalse()
        ->and($result->isFailure())->toBeTrue()
        ->and($result->getError())->toBe($errorMessage);
});

it('can be created with direct constructor', function () {
    $awards = [
        new Award('Test', '🔥', 'Manager', 'Description', 1.0),
    ];
    $leagueData = createMockLeagueData();

    $result = new PerformanceAwardsResults(
        success: true,
        awards: $awards,
        league: $leagueData,
        week: 3
    );

    expect($result->success)->toBeTrue()
        ->and($result->awards)->toBe($awards)
        ->and($result->league)->toBe($leagueData)
        ->and($result->week)->toBe(3);
});

it('stores empty awards array correctly', function () {
    $leagueData = createMockLeagueData();

    $result = PerformanceAwardsResults::success([], $leagueData, 3);

    expect($result->awards)->toBe([])
        ->and($result->awards)->toHaveCount(0);
});

it('stores multiple awards correctly', function () {
    $awards = [
        new Award('The Money Shot', '💰', 'Manager One', 'QB scored 25.5 points', 25.5),
        new Award('The Taco', '🌮', 'Manager Two', 'Scored only 95.0 points', 95.0),
        new Award('Best Manager', '🔥', 'Manager One', '95.2% efficiency', 95.2),
        new Award('Worst Manager', '🤔', 'Manager Two', '78.1% efficiency', 78.1),
        new Award('Biggest Blowout', '😂', 'Manager One', 'Won by 47.5 points', 47.5),
    ];

    $leagueData = createMockLeagueData();
    $result = PerformanceAwardsResults::success($awards, $leagueData, 3);

    expect($result->awards)->toHaveCount(5)
        ->and($result->awards[0]->title)->toBe('The Money Shot')
        ->and($result->awards[1]->title)->toBe('The Taco')
        ->and($result->awards[2]->title)->toBe('Best Manager')
        ->and($result->awards[3]->title)->toBe('Worst Manager')
        ->and($result->awards[4]->title)->toBe('Biggest Blowout');
});

it('preserves award order as provided', function () {
    // Create awards in specific order
    $awards = [
        new Award('Z Award', '🔥', 'Manager', 'Description', 1.0),
        new Award('A Award', '🔥', 'Manager', 'Description', 2.0),
        new Award('M Award', '🔥', 'Manager', 'Description', 3.0),
    ];

    $leagueData = createMockLeagueData();
    $result = PerformanceAwardsResults::success($awards, $leagueData, 3);

    // Should maintain the order we provided, not alphabetical
    expect($result->awards[0]->title)->toBe('Z Award')
        ->and($result->awards[1]->title)->toBe('A Award')
        ->and($result->awards[2]->title)->toBe('M Award');
});

it('handles different week values correctly', function () {
    $leagueData = createMockLeagueData();
    $awards = [new Award('Test', '🔥', 'Manager', 'Description', 1.0)];

    // Test different week values
    foreach ([1, 5, 10, 15, 18] as $weekNumber) {
        $result = PerformanceAwardsResults::success($awards, $leagueData, $weekNumber);

        expect($result->week)->toBe($weekNumber);
    }
});

it('handles long error messages', function () {
    $longError = 'This is a very long error message that might occur when there are multiple issues with the API connection or data validation. It should be stored and retrieved correctly without truncation or modification.';

    $result = PerformanceAwardsResults::failure($longError);

    expect($result->getError())->toBe($longError);
});

it('maintains immutability of league data object', function () {
    $leagueData = createMockLeagueData();
    $awards = [new Award('Test', '🔥', 'Manager', 'Description', 1.0)];

    $result = PerformanceAwardsResults::success($awards, $leagueData, 3);

    // The stored objects should be the same instances
    expect($result->league)->toBe($leagueData);
});

function createMockLeagueData()
{
    $leagueId = new LeagueId('123456789');

    return new LeagueData(
        $leagueId,
        'Test League',
        2,
        new Week(15),
        new Week(3),
        [],
        new \App\DataTransferObjects\League\WeeklySchedule,
        (object) ['name' => 'Test League'],
        [],
        [],
        []
    );
}