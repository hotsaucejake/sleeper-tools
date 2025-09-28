<?php

use App\DataTransferObjects\League\WeeklySchedule;
use App\Services\Fantasy\ScheduleAnalysisService;

it('builds schedule from matchups correctly', function () {
    $service = new ScheduleAnalysisService;

    $matchups = [
        1 => [
            (object) ['roster_id' => 1, 'points' => 120.5, 'matchup_id' => 1],
            (object) ['roster_id' => 2, 'points' => 115.3, 'matchup_id' => 1],
            (object) ['roster_id' => 3, 'points' => 110.0, 'matchup_id' => 2],
            (object) ['roster_id' => 4, 'points' => 105.7, 'matchup_id' => 2],
        ],
        2 => [
            (object) ['roster_id' => 1, 'points' => 130.2, 'matchup_id' => 1],
            (object) ['roster_id' => 3, 'points' => 125.8, 'matchup_id' => 1],
            (object) ['roster_id' => 2, 'points' => 118.5, 'matchup_id' => 2],
            (object) ['roster_id' => 4, 'points' => 112.3, 'matchup_id' => 2],
        ],
    ];

    $result = $service->buildScheduleFromMatchups($matchups);

    expect($result)->toBeInstanceOf(WeeklySchedule::class)
        ->and($result->getWeekCount())->toBe(2)
        ->and($result->getAllWeeks())->toContain(1, 2);

    // Test specific matchup data
    $week1Schedule = $result->getWeekSchedule(new \App\ValueObjects\Week(1));
    expect($week1Schedule)->toHaveKey(1)
        ->and($week1Schedule[1]['score'])->toBe(120.5)
        ->and($week1Schedule[1]['vs'])->toBe(2);
});

it('adds schedules to managers correctly', function () {
    $service = new ScheduleAnalysisService;

    $managers = [
        1 => ['roster_id' => 1, 'name' => 'Team 1'],
        2 => ['roster_id' => 2, 'name' => 'Team 2'],
    ];

    $schedule = new WeeklySchedule;
    $schedule->addMatchup(
        new \App\ValueObjects\Week(1),
        new \App\ValueObjects\RosterId(1),
        ['score' => 120.5, 'vs' => 2, 'roster_id' => 1]
    );
    $schedule->addMatchup(
        new \App\ValueObjects\Week(1),
        new \App\ValueObjects\RosterId(2),
        ['score' => 115.3, 'vs' => 1, 'roster_id' => 2]
    );

    $result = $service->addSchedulesToManagers($managers, $schedule);

    expect($result[1])->toHaveKey('schedule')
        ->and($result[1]['schedule'])->toHaveKey(1)
        ->and($result[1]['schedule'][1]['score'])->toBe(120.5)
        ->and($result[1])->toHaveKey('records')
        ->and($result[1]['records'])->toHaveKey(1)
        ->and($result[1]['records'])->toHaveKey(2)
        ->and($result[1]['records'][1]['win'])->toBe(0)
        ->and($result[1]['records'][1]['loss'])->toBe(0);
});

it('initializes records for all managers', function () {
    $service = new ScheduleAnalysisService;

    $managers = [
        1 => ['roster_id' => 1, 'name' => 'Team 1'],
        2 => ['roster_id' => 2, 'name' => 'Team 2'],
        3 => ['roster_id' => 3, 'name' => 'Team 3'],
    ];

    $result = $service->initializeRecords($managers);

    expect($result[1]['records'])->toHaveKey(1)
        ->and($result[1]['records'])->toHaveKey(2)
        ->and($result[1]['records'])->toHaveKey(3)
        ->and($result[1]['records'][2]['name'])->toBe('Team 2')
        ->and($result[1]['records'][2]['roster_id'])->toBe(2)
        ->and($result[1]['records'][2]['win'])->toBe(0)
        ->and($result[1]['records'][2]['loss'])->toBe(0);
});

it('handles empty matchups gracefully', function () {
    $service = new ScheduleAnalysisService;

    $matchups = [];

    $result = $service->buildScheduleFromMatchups($matchups);

    expect($result)->toBeInstanceOf(WeeklySchedule::class)
        ->and($result->getWeekCount())->toBe(0);
});
