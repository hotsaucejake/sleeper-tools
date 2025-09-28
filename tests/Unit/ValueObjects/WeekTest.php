<?php

use App\ValueObjects\Week;

it('creates a valid week', function () {
    $week = new Week(8);

    expect($week->toInt())->toBe(8)
        ->and((string) $week)->toBe('8');
});

it('throws exception for week below 1', function () {
    expect(fn() => new Week(0))->toThrow(InvalidArgumentException::class, 'Week must be between 1 and 22');
});

it('throws exception for week above 22', function () {
    expect(fn() => new Week(23))->toThrow(InvalidArgumentException::class, 'Week must be between 1 and 22');
});

it('identifies regular season weeks correctly', function () {
    $regularWeek = new Week(10);
    $playoffWeek = new Week(20);

    expect($regularWeek->isRegularSeason())->toBeTrue()
        ->and($regularWeek->isPlayoffs())->toBeFalse()
        ->and($playoffWeek->isRegularSeason())->toBeFalse()
        ->and($playoffWeek->isPlayoffs())->toBeTrue();
});

it('handles boundary weeks correctly', function () {
    $week18 = new Week(18);
    $week19 = new Week(19);

    expect($week18->isRegularSeason())->toBeTrue()
        ->and($week18->isPlayoffs())->toBeFalse()
        ->and($week19->isRegularSeason())->toBeFalse()
        ->and($week19->isPlayoffs())->toBeTrue();
});

it('compares weeks correctly', function () {
    $week1 = new Week(8);
    $week2 = new Week(8);
    $week3 = new Week(15);

    expect($week1->equals($week2))->toBeTrue()
        ->and($week1->equals($week3))->toBeFalse();
});