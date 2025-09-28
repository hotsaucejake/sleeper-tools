<?php

use App\ValueObjects\LeagueId;

it('creates a valid league id', function () {
    $leagueId = new LeagueId('1133124905354973184');

    expect($leagueId->toString())->toBe('1133124905354973184')
        ->and((string) $leagueId)->toBe('1133124905354973184');
});

it('throws exception for empty league id', function () {
    expect(fn () => new LeagueId(''))->toThrow(InvalidArgumentException::class, 'League ID must be a non-empty numeric string');
});

it('throws exception for non-numeric league id', function () {
    expect(fn () => new LeagueId('abc123'))->toThrow(InvalidArgumentException::class, 'League ID must be a non-empty numeric string');
});

it('compares league ids correctly', function () {
    $id1 = new LeagueId('123456789');
    $id2 = new LeagueId('123456789');
    $id3 = new LeagueId('987654321');

    expect($id1->equals($id2))->toBeTrue()
        ->and($id1->equals($id3))->toBeFalse();
});

it('accepts numeric strings with leading zeros', function () {
    $leagueId = new LeagueId('0123456789');

    expect($leagueId->toString())->toBe('0123456789');
});
