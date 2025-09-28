<?php

use App\DataTransferObjects\Analysis\Award;

it('creates award with all required properties', function () {
    $award = new Award(
        'The Money Shot',
        '💰',
        'Manager One',
        'Test QB scored 25.5 points',
        25.5,
        null,
        ['name' => 'Test QB', 'position' => 'QB', 'team' => 'TB', 'points' => 25.5]
    );

    expect($award->title)->toBe('The Money Shot')
        ->and($award->emoji)->toBe('💰')
        ->and($award->managerName)->toBe('Manager One')
        ->and($award->description)->toBe('Test QB scored 25.5 points')
        ->and($award->value)->toBe(25.5)
        ->and($award->secondaryManagerName)->toBeNull()
        ->and($award->playerInfo)->toBe(['name' => 'Test QB', 'position' => 'QB', 'team' => 'TB', 'points' => 25.5]);
});

it('creates award with null player info', function () {
    $award = new Award(
        'The Taco',
        '🌮',
        'Manager Two',
        'Scored only 95.0 points',
        95.0
    );

    expect($award->title)->toBe('The Taco')
        ->and($award->emoji)->toBe('🌮')
        ->and($award->managerName)->toBe('Manager Two')
        ->and($award->description)->toBe('Scored only 95.0 points')
        ->and($award->value)->toBe(95.0)
        ->and($award->secondaryManagerName)->toBeNull()
        ->and($award->playerInfo)->toBeNull();
});

it('creates award with empty player info array', function () {
    $award = new Award(
        'Best Manager',
        '🔥',
        'Manager One',
        '95.2% efficiency',
        95.2,
        null,
        []
    );

    expect($award->playerInfo)->toBe([]);
});

it('creates award with secondary manager name', function () {
    $award = new Award(
        'Biggest Blowout',
        '😂',
        'Manager One',
        'Defeated Manager Two by 47.5 points',
        47.5,
        'Manager Two'
    );

    expect($award->managerName)->toBe('Manager One')
        ->and($award->secondaryManagerName)->toBe('Manager Two')
        ->and($award->value)->toBe(47.5);
});

it('handles special characters in description', function () {
    $description = 'Defeated opponent by 47.5 points (142.5 vs 95.0)';

    $award = new Award(
        'Biggest Blowout',
        '😂',
        'Manager One',
        $description,
        47.5
    );

    expect($award->description)->toBe($description);
});

it('handles long manager names', function () {
    $longName = 'Really Long Manager Name That Might Wrap';

    $award = new Award(
        'Narrow Victory',
        '😱',
        $longName,
        'Won by 0.1 points',
        0.1
    );

    expect($award->managerName)->toBe($longName);
});

it('stores complete player information correctly', function () {
    $playerInfo = [
        'name' => 'Christian McCaffrey',
        'position' => 'RB',
        'team' => 'SF',
        'points' => 28.7,
        'avatar' => 'https://sleepercdn.com/content/nfl/players/thumb/4035.jpg',
    ];

    $award = new Award(
        'RB of the Week',
        '⭐',
        'Manager One',
        'Christian McCaffrey scored 28.7 points',
        28.7,
        null,
        $playerInfo
    );

    expect($award->playerInfo)->toBe($playerInfo)
        ->and($award->playerInfo['name'])->toBe('Christian McCaffrey')
        ->and($award->playerInfo['position'])->toBe('RB')
        ->and($award->playerInfo['team'])->toBe('SF')
        ->and($award->playerInfo['points'])->toBe(28.7)
        ->and($award->playerInfo['avatar'])->toBe('https://sleepercdn.com/content/nfl/players/thumb/4035.jpg');
});

it('handles different value types', function () {
    // Test with integer
    $award1 = new Award('Test', '🔥', 'Manager', 'Description', 100);
    expect($award1->value)->toBe(100.0); // Will be cast to float

    // Test with float
    $award2 = new Award('Test', '🔥', 'Manager', 'Description', 25.5);
    expect($award2->value)->toBe(25.5);

    // Test with zero
    $award3 = new Award('Test', '🔥', 'Manager', 'Description', 0);
    expect($award3->value)->toBe(0.0);
});

it('preserves emoji characters in emoji field', function () {
    $emojis = ['💰', '🌮', '🔥', '🤔', '😂', '😱', '🤓', '💀', '⭐', '👀', '🍆'];

    foreach ($emojis as $emoji) {
        $award = new Award(
            'Test Award',
            $emoji,
            'Manager',
            'Description',
            1.0
        );

        expect($award->emoji)->toBe($emoji);
    }
});
