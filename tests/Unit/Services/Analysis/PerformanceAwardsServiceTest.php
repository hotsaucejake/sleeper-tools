<?php

use App\DataTransferObjects\Analysis\PerformanceAwardsResults;
use App\Services\Analysis\PerformanceAwardsService;
use App\Services\Sleeper\LeagueDataService;

it('implements performance awards interface', function () {
    $mockLeagueDataService = Mockery::mock(LeagueDataService::class);

    $service = new PerformanceAwardsService($mockLeagueDataService);

    expect($service)->toBeInstanceOf(\App\Services\Analysis\Contracts\PerformanceAwardsInterface::class);
});

it('has correct method signature for weekly performance analysis', function () {
    $reflection = new ReflectionClass(PerformanceAwardsService::class);
    $method = $reflection->getMethod('analyzeWeeklyPerformance');

    expect($method->isPublic())->toBeTrue();

    $parameters = $method->getParameters();
    expect($parameters)->toHaveCount(2)
        ->and($parameters[0]->getName())->toBe('leagueId')
        ->and($parameters[1]->getName())->toBe('week');

    $returnType = $method->getReturnType();
    expect($returnType->getName())->toBe(PerformanceAwardsResults::class);
});

it('has calculate award tallies method', function () {
    $reflection = new ReflectionClass(PerformanceAwardsService::class);
    $method = $reflection->getMethod('calculateAwardTallies');

    expect($method->isPublic())->toBeTrue();

    $parameters = $method->getParameters();
    expect($parameters)->toHaveCount(2)
        ->and($parameters[0]->getName())->toBe('leagueId')
        ->and($parameters[1]->getName())->toBe('throughWeek');

    $returnType = $method->getReturnType();
    expect($returnType->getName())->toBe('array');
});
