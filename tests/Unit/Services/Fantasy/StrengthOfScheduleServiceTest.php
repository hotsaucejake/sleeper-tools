<?php

use App\Services\Fantasy\StrengthOfScheduleService;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Log::shouldReceive('info')->andReturn(null);
});

it('calculates overall wins and losses correctly', function () {
    $service = new StrengthOfScheduleService();

    $managers = [
        1 => [
            'records' => [
                1 => ['win' => 2, 'loss' => 1],
                2 => ['win' => 1, 'loss' => 2],
                3 => ['win' => 3, 'loss' => 0]
            ]
        ],
        2 => [
            'records' => [
                1 => ['win' => 1, 'loss' => 2],
                2 => ['win' => 2, 'loss' => 1],
                3 => ['win' => 0, 'loss' => 3]
            ]
        ],
        3 => [
            'records' => [
                1 => ['win' => 0, 'loss' => 3],
                2 => ['win' => 3, 'loss' => 0],
                3 => ['win' => 1, 'loss' => 2]
            ]
        ]
    ];

    $result = $service->calculateOverallWinsLosses($managers);

    expect($result)->toHaveKeys(['wins', 'losses'])
        ->and($result['wins'])->toHaveKey(1)
        ->and($result['wins'])->toHaveKey(2)
        ->and($result['wins'])->toHaveKey(3)
        ->and($result['losses'])->toHaveKey(1)
        ->and($result['losses'])->toHaveKey(2)
        ->and($result['losses'])->toHaveKey(3);

    // Team 1: Accumulated wins from all managers' records against team 1
    // Manager 1 vs Team 1: 2 wins, Manager 2 vs Team 1: 1 win, Manager 3 vs Team 1: 0 wins = 3 total
    expect($result['wins'][1])->toBe(3);

    // Team 1: Accumulated losses from all managers' records against team 1
    // Manager 1 vs Team 1: 1 loss, Manager 2 vs Team 1: 2 losses, Manager 3 vs Team 1: 3 losses = 6 total
    expect($result['losses'][1])->toBe(6);
});

it('ranks by strength of schedule correctly', function () {
    $service = new StrengthOfScheduleService();

    $overallLosses = [
        1 => 15, // Easiest schedule (fewest losses)
        2 => 25, // Hardest schedule (most losses)
        3 => 20  // Middle
    ];

    $result = $service->rankByStrengthOfSchedule($overallLosses);

    // Should be sorted in descending order (most losses first = toughest schedule)
    $keys = array_keys($result);
    expect($keys[0])->toBe(2) // Team 2 had toughest schedule (25 losses)
        ->and($keys[1])->toBe(3) // Team 3 had middle schedule (20 losses)
        ->and($keys[2])->toBe(1); // Team 1 had easiest schedule (15 losses)
});

it('generates complete strength analysis', function () {
    $service = new StrengthOfScheduleService();

    $managers = [
        1 => [
            'records' => [
                1 => ['win' => 2, 'loss' => 1],
                2 => ['win' => 1, 'loss' => 2]
            ]
        ],
        2 => [
            'records' => [
                1 => ['win' => 1, 'loss' => 2],
                2 => ['win' => 2, 'loss' => 1]
            ]
        ]
    ];

    $result = $service->generateStrengthAnalysis($managers);

    expect($result)->toHaveKeys(['overall_wins', 'overall_losses', 'rankings'])
        ->and($result['overall_wins'])->toBeArray()
        ->and($result['overall_losses'])->toBeArray()
        ->and($result['rankings'])->toBeArray()
        ->and($result['rankings'])->toBe(array_keys($result['overall_losses']));
});

it('handles empty managers array', function () {
    $service = new StrengthOfScheduleService();

    $managers = [];

    $result = $service->calculateOverallWinsLosses($managers);

    expect($result['wins'])->toBeArray()
        ->and($result['losses'])->toBeArray()
        ->and($result['wins'])->toBeEmpty()
        ->and($result['losses'])->toBeEmpty();
});