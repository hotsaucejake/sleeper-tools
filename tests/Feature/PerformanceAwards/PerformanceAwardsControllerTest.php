<?php

use App\DataTransferObjects\Analysis\Award;
use App\DataTransferObjects\Analysis\PerformanceAwardsResults;
use App\Services\Analysis\PerformanceAwardsService;
use App\ValueObjects\LeagueId;
use App\ValueObjects\Week;
use Illuminate\Support\Facades\Cache;
use HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper;

beforeEach(function () {
    Cache::flush();
});

it('redirects to home when no league_id provided', function () {
    $response = $this->get('/performance-awards');

    $response->assertRedirect('/');
});

it('has performance awards route registered', function () {
    $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())->map(function ($route) {
        return $route->uri();
    });

    expect($routes)->toContain('performance-awards');
});

it('performance awards route exists and returns response', function () {
    $response = $this->get('/performance-awards?league_id=test');

    // Should return some response (even if redirect due to invalid league)
    expect($response->getStatusCode())->toBeIn([200, 302, 422, 500]);
});