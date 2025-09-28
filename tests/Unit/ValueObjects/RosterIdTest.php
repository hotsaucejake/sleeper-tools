<?php

use App\ValueObjects\RosterId;

it('creates a valid roster id', function () {
    $rosterId = new RosterId(5);

    expect($rosterId->toInt())->toBe(5)
        ->and((string) $rosterId)->toBe('5');
});

it('throws exception for zero roster id', function () {
    expect(fn() => new RosterId(0))->toThrow(InvalidArgumentException::class, 'Roster ID must be a positive integer');
});

it('throws exception for negative roster id', function () {
    expect(fn() => new RosterId(-1))->toThrow(InvalidArgumentException::class, 'Roster ID must be a positive integer');
});

it('compares roster ids correctly', function () {
    $id1 = new RosterId(5);
    $id2 = new RosterId(5);
    $id3 = new RosterId(10);

    expect($id1->equals($id2))->toBeTrue()
        ->and($id1->equals($id3))->toBeFalse();
});

it('handles large roster ids', function () {
    $rosterId = new RosterId(999999);

    expect($rosterId->toInt())->toBe(999999);
});