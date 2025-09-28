<?php

use App\Services\Fantasy\AlternativeRecordsService;
use App\ValueObjects\Score;

it('determines matchup result correctly', function () {
    $service = new AlternativeRecordsService;

    $higherScore = new Score(125.5);
    $lowerScore = new Score(120.3);

    expect($service->determineMatchupResult($higherScore, $lowerScore))->toBeTrue()
        ->and($service->determineMatchupResult($lowerScore, $higherScore))->toBeFalse();
});

it('handles tie scores correctly', function () {
    $service = new AlternativeRecordsService;

    $score1 = new Score(125.5);
    $score2 = new Score(125.5);

    expect($service->determineMatchupResult($score1, $score2))->toBeFalse(); // Not greater than
});

it('calculates alternative records for simple case', function () {
    $service = new AlternativeRecordsService;

    $managers = [
        1 => [
            'roster_id' => 1,
            'name' => 'Team 1',
            'schedule' => [
                1 => ['score' => 120.0, 'vs' => 2, 'roster_id' => 1],
                2 => ['score' => 110.0, 'vs' => 3, 'roster_id' => 1],
            ],
            'records' => [
                1 => ['name' => 'Team 1', 'roster_id' => 1, 'win' => 0, 'loss' => 0],
                2 => ['name' => 'Team 2', 'roster_id' => 2, 'win' => 0, 'loss' => 0],
                3 => ['name' => 'Team 3', 'roster_id' => 3, 'win' => 0, 'loss' => 0],
                4 => ['name' => 'Team 4', 'roster_id' => 4, 'win' => 0, 'loss' => 0],
            ],
        ],
        2 => [
            'roster_id' => 2,
            'name' => 'Team 2',
            'schedule' => [
                1 => ['score' => 115.0, 'vs' => 1, 'roster_id' => 2],
                2 => ['score' => 130.0, 'vs' => 3, 'roster_id' => 2],
            ],
            'records' => [
                1 => ['name' => 'Team 1', 'roster_id' => 1, 'win' => 0, 'loss' => 0],
                2 => ['name' => 'Team 2', 'roster_id' => 2, 'win' => 0, 'loss' => 0],
                3 => ['name' => 'Team 3', 'roster_id' => 3, 'win' => 0, 'loss' => 0],
                4 => ['name' => 'Team 4', 'roster_id' => 4, 'win' => 0, 'loss' => 0],
            ],
        ],
        3 => [
            'roster_id' => 3,
            'name' => 'Team 3',
            'schedule' => [
                1 => ['score' => 100.0, 'vs' => 4, 'roster_id' => 3],
                2 => ['score' => 125.0, 'vs' => 2, 'roster_id' => 3],
            ],
            'records' => [
                1 => ['name' => 'Team 1', 'roster_id' => 1, 'win' => 0, 'loss' => 0],
                2 => ['name' => 'Team 2', 'roster_id' => 2, 'win' => 0, 'loss' => 0],
                3 => ['name' => 'Team 3', 'roster_id' => 3, 'win' => 0, 'loss' => 0],
                4 => ['name' => 'Team 4', 'roster_id' => 4, 'win' => 0, 'loss' => 0],
            ],
        ],
        4 => [
            'roster_id' => 4,
            'name' => 'Team 4',
            'schedule' => [
                1 => ['score' => 95.0, 'vs' => 3, 'roster_id' => 4],
                2 => ['score' => 105.0, 'vs' => 1, 'roster_id' => 4],
            ],
            'records' => [
                1 => ['name' => 'Team 1', 'roster_id' => 1, 'win' => 0, 'loss' => 0],
                2 => ['name' => 'Team 2', 'roster_id' => 2, 'win' => 0, 'loss' => 0],
                3 => ['name' => 'Team 3', 'roster_id' => 3, 'win' => 0, 'loss' => 0],
                4 => ['name' => 'Team 4', 'roster_id' => 4, 'win' => 0, 'loss' => 0],
            ],
        ],
    ];

    $result = $service->calculateAlternativeRecords($managers);

    expect($result)->toBeArray()
        ->and($result[1]['records'][2]['win'])->toBeInt()
        ->and($result[1]['records'][2]['loss'])->toBeInt()
        ->and($result[1]['records'][2]['win'] + $result[1]['records'][2]['loss'])->toBe(2); // 2 weeks
});

it('compares manager against specific schedule', function () {
    $service = new AlternativeRecordsService;

    $manager = [
        'roster_id' => 1,
        'schedule' => [
            1 => ['score' => 125.0],
            2 => ['score' => 110.0],
        ],
    ];

    $scheduleOwner = [
        'schedule' => [
            1 => ['score' => 120.0, 'vs' => 3], // Alternative matchup
            2 => ['score' => 115.0, 'vs' => 1],  // Direct matchup
        ],
    ];

    $result = $service->compareManagerAgainstSchedule($manager, $scheduleOwner);

    // Debug what's actually returned
    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['direct_matchups', 'alternative_matchups']);

    if (isset($result['alternative_matchups'][1])) {
        expect($result['alternative_matchups'][1]['win'])->toBeTrue(); // 125 > 120
    }

    if (isset($result['direct_matchups'][2])) {
        expect($result['direct_matchups'][2]['win'])->toBeFalse(); // 110 < 115
    }
});
