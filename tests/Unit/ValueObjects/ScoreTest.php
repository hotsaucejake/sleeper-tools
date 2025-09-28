<?php

use App\ValueObjects\Score;

it('creates a valid score', function () {
    $score = new Score(125.75);

    expect($score->toFloat())->toBe(125.75)
        ->and((string) $score)->toBe('125.75');
});

it('throws exception for negative score', function () {
    expect(fn() => new Score(-10.5))->toThrow(InvalidArgumentException::class, 'Score cannot be negative');
});

it('accepts zero score', function () {
    $score = new Score(0.0);

    expect($score->toFloat())->toBe(0.0);
});

it('compares scores correctly', function () {
    $score1 = new Score(125.5);
    $score2 = new Score(130.0);
    $score3 = new Score(125.5);

    expect($score1->isGreaterThan($score2))->toBeFalse()
        ->and($score2->isGreaterThan($score1))->toBeTrue()
        ->and($score1->isLessThan($score2))->toBeTrue()
        ->and($score2->isLessThan($score1))->toBeFalse()
        ->and($score1->equals($score3))->toBeTrue()
        ->and($score1->equals($score2))->toBeFalse();
});

it('handles floating point precision in equality', function () {
    $score1 = new Score(125.123456);
    $score2 = new Score(125.123457); // Tiny difference

    // Should be equal within precision tolerance
    expect($score1->equals($score2))->toBeTrue();
});

it('handles decimal scores correctly', function () {
    $score = new Score(158.62);

    expect($score->toFloat())->toBe(158.62);
});

it('handles whole number scores', function () {
    $score = new Score(100);

    expect($score->toFloat())->toBe(100.0);
});