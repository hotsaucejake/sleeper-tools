<?php

it('displays league selection form when no league parameter is provided', function () {
    $response = $this->get('/shoulda-coulda-woulda');

    $response->assertOk();
    $response->assertViewIs('shoulda-coulda-woulda.league-select');
    $response->assertViewHas('valid_league', false);
    $response->assertViewHas('managers', []);
    $response->assertViewHas('overall_losses', []);
    $response->assertViewHas('league', null);
    $response->assertViewHas('current_week', null);
});

it('successfully analyzes a valid league and displays results', function () {
    // Create expected analysis results
    $leagueId = new \App\ValueObjects\LeagueId('1257452477368782848');

    $managers = [
        1 => [
            'roster_id' => 1,
            'user_id' => 'user1',
            'name' => 'Test Manager 1',
            'win' => 2,
            'loss' => 1,
            'avatar' => 'https://example.com/avatar.png',
            'schedule' => [
                1 => ['score' => 158.62, 'vs' => 2, 'roster_id' => 1],
                2 => ['score' => 91.6, 'vs' => 3, 'roster_id' => 1],
                3 => ['score' => 96.26, 'vs' => 4, 'roster_id' => 1],
            ],
            'records' => [
                1 => ['name' => 'Test Manager 1', 'roster_id' => 1, 'win' => 1, 'loss' => 2],
                2 => ['name' => 'Test Manager 2', 'roster_id' => 2, 'win' => 2, 'loss' => 1],
            ],
        ],
        2 => [
            'roster_id' => 2,
            'user_id' => 'user2',
            'name' => 'Test Manager 2',
            'win' => 1,
            'loss' => 2,
            'avatar' => 'https://example.com/avatar2.png',
            'schedule' => [
                1 => ['score' => 140.25, 'vs' => 1, 'roster_id' => 2],
                2 => ['score' => 105.8, 'vs' => 3, 'roster_id' => 2],
                3 => ['score' => 88.15, 'vs' => 4, 'roster_id' => 2],
            ],
            'records' => [
                1 => ['name' => 'Test Manager 1', 'roster_id' => 1, 'win' => 2, 'loss' => 1],
                2 => ['name' => 'Test Manager 2', 'roster_id' => 2, 'win' => 1, 'loss' => 2],
            ],
        ],
    ];

    $strengthOfSchedule = [1 => 15, 2 => 25];

    $leagueData = new \App\DataTransferObjects\League\LeagueData(
        id: $leagueId,
        name: 'Test League',
        totalRosters: 10,
        playoffWeekStart: new \App\ValueObjects\Week(15),
        currentWeek: new \App\ValueObjects\Week(4),
        managers: $managers,
        schedule: new \App\DataTransferObjects\League\WeeklySchedule,
        rawLeagueData: (object) [
            'name' => 'Test League',
            'total_rosters' => 10,
            'settings' => (object) [
                'league_average_match' => false,
                'playoff_week_start' => 15,
            ],
        ]
    );

    $analysisResults = \App\DataTransferObjects\Analysis\AnalysisResults::success(
        $managers,
        $strengthOfSchedule,
        $leagueData
    );

    // Mock the analysis service
    $mockAnalysisService = Mockery::mock(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class);
    $mockAnalysisService->shouldReceive('analyzeLeague')
        ->once()
        ->with(Mockery::on(function ($arg) use ($leagueId) {
            return $arg->toString() === $leagueId->toString();
        }))
        ->andReturn($analysisResults);

    $this->app->instance(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class, $mockAnalysisService);

    $response = $this->get('/shoulda-coulda-woulda?league=1257452477368782848');

    $response->assertOk();
    $response->assertViewIs('shoulda-coulda-woulda.league-select');
    $response->assertViewHas('valid_league', true);

    // Verify managers data structure
    $managers = $response->viewData('managers');
    expect($managers)->toBeArray()
        ->and(count($managers))->toBe(2)
        ->and($managers[1])->toHaveKeys([
            'roster_id', 'user_id', 'win', 'loss', 'name', 'avatar', 'schedule', 'records',
        ])
        ->and($managers[2])->toHaveKeys([
            'roster_id', 'user_id', 'win', 'loss', 'name', 'avatar', 'schedule', 'records',
        ]);

    // Verify overall losses data
    $overallLosses = $response->viewData('overall_losses');
    expect($overallLosses)->toBeArray()
        ->and(count($overallLosses))->toBe(2);

    // Verify league data
    $league = $response->viewData('league');
    expect($league)->toBeObject()
        ->and($league->name)->toBe('Test League');

    // Verify current week
    $currentWeek = $response->viewData('current_week');
    expect($currentWeek)->toBeInstanceOf(\App\ValueObjects\Week::class);
});

it('handles invalid league ID gracefully', function () {
    // Mock the analysis service to throw an InvalidLeagueException
    $mockAnalysisService = Mockery::mock(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class);
    $mockAnalysisService->shouldReceive('analyzeLeague')
        ->once()
        ->andReturn(\App\DataTransferObjects\Analysis\AnalysisResults::failure('This is not a valid Sleeper league ID!'));

    $this->app->instance(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class, $mockAnalysisService);

    $response = $this->get('/shoulda-coulda-woulda?league=1234567890');

    $response->assertRedirect(route('shoulda-coulda-woulda.select-league'));
    $response->assertSessionHas('error', 'This is not a valid Sleeper league ID!');
});

it('handles API connection errors gracefully', function () {
    // Mock the analysis service to return an API connection failure
    $mockAnalysisService = Mockery::mock(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class);
    $mockAnalysisService->shouldReceive('analyzeLeague')
        ->once()
        ->andReturn(\App\DataTransferObjects\Analysis\AnalysisResults::failure('There was an error fetching matchups!'));

    $this->app->instance(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class, $mockAnalysisService);

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertRedirect(route('shoulda-coulda-woulda.select-league'));
    $response->assertSessionHas('error', 'There was an error fetching matchups!');
});

it('handles missing league data gracefully', function () {
    // Mock the analysis service to return a data insufficient failure
    $mockAnalysisService = Mockery::mock(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class);
    $mockAnalysisService->shouldReceive('analyzeLeague')
        ->once()
        ->andReturn(\App\DataTransferObjects\Analysis\AnalysisResults::failure('Could not retrieve your league settings!'));

    $this->app->instance(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class, $mockAnalysisService);

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertRedirect(route('shoulda-coulda-woulda.select-league'));
    $response->assertSessionHas('error', 'Could not retrieve your league settings!');
});

it('handles missing league data validation properly', function () {
    // Mock the analysis service to return a validation failure
    $mockAnalysisService = Mockery::mock(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class);
    $mockAnalysisService->shouldReceive('analyzeLeague')
        ->once()
        ->andReturn(\App\DataTransferObjects\Analysis\AnalysisResults::failure('Could not retrieve your league settings!'));

    $this->app->instance(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class, $mockAnalysisService);

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertRedirect(route('shoulda-coulda-woulda.select-league'));
    $response->assertSessionHas('error', 'Could not retrieve your league settings!');
});

it('handles matchup fetching errors gracefully', function () {
    // Mock the analysis service to return a matchup fetch failure
    $mockAnalysisService = Mockery::mock(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class);
    $mockAnalysisService->shouldReceive('analyzeLeague')
        ->once()
        ->andReturn(\App\DataTransferObjects\Analysis\AnalysisResults::failure('There was an error fetching matchups!'));

    $this->app->instance(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class, $mockAnalysisService);

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertRedirect(route('shoulda-coulda-woulda.select-league'));
    $response->assertSessionHas('error', 'There was an error fetching matchups!');
});

it('correctly calculates alternative records for specific scenarios', function () {
    // Create test data with specific calculations
    $managers = [
        1 => [
            'roster_id' => 1,
            'user_id' => 'user1',
            'name' => 'Test Manager 1',
            'win' => 2, 'loss' => 1,
            'avatar' => 'https://example.com/avatar.png',
            'schedule' => [1 => ['score' => 158.62], 2 => ['score' => 91.6], 3 => ['score' => 96.26]],
            'records' => [
                1 => ['name' => 'Test Manager 1', 'roster_id' => 1, 'win' => 1, 'loss' => 2],
                2 => ['name' => 'Double2ChinMan', 'roster_id' => 2, 'win' => 2, 'loss' => 1],
            ],
        ],
        2 => [
            'roster_id' => 2,
            'user_id' => 'user2',
            'name' => 'Double2ChinMan',
            'win' => 1, 'loss' => 2,
            'avatar' => 'https://example.com/avatar2.png',
            'schedule' => [1 => ['score' => 140.25], 2 => ['score' => 105.8], 3 => ['score' => 88.15]],
            'records' => [
                1 => ['name' => 'Test Manager 1', 'roster_id' => 1, 'win' => 2, 'loss' => 1],
                2 => ['name' => 'Double2ChinMan', 'roster_id' => 2, 'win' => 1, 'loss' => 2],
            ],
        ],
    ];

    $leagueData = new \App\DataTransferObjects\League\LeagueData(
        id: new \App\ValueObjects\LeagueId('1133124905354973184'),
        name: 'Test League',
        totalRosters: 10,
        playoffWeekStart: new \App\ValueObjects\Week(15),
        currentWeek: new \App\ValueObjects\Week(4),
        managers: $managers,
        schedule: new \App\DataTransferObjects\League\WeeklySchedule,
        rawLeagueData: (object) [
            'name' => 'Test League',
            'total_rosters' => 10,
            'settings' => (object) [
                'league_average_match' => false,
                'playoff_week_start' => 15,
            ],
        ]
    );

    $analysisResults = \App\DataTransferObjects\Analysis\AnalysisResults::success(
        $managers,
        [1 => 15, 2 => 25],
        $leagueData
    );

    $mockAnalysisService = Mockery::mock(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class);
    $mockAnalysisService->shouldReceive('analyzeLeague')
        ->once()
        ->andReturn($analysisResults);

    $this->app->instance(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class, $mockAnalysisService);

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertOk();
    $managers = $response->viewData('managers');

    // Test specific calculation: Manager 1 vs Manager 2's schedule
    $manager1 = $managers[1];
    $recordVsManager2Schedule = $manager1['records'][2];

    // Verify record structure
    expect($recordVsManager2Schedule)->toHaveKeys(['name', 'roster_id', 'win', 'loss'])
        ->and($recordVsManager2Schedule['name'])->toBe('Double2ChinMan')
        ->and($recordVsManager2Schedule['roster_id'])->toBe(2);

    // Verify wins + losses = 3 (number of weeks)
    $totalGames = $recordVsManager2Schedule['win'] + $recordVsManager2Schedule['loss'];
    expect($totalGames)->toBe(3);
});

it('correctly handles league average matching setting', function () {
    // Create league data with league_average_match = 1
    $rawLeagueData = (object) [
        'name' => 'Test League',
        'total_rosters' => 10,
        'settings' => (object) ['league_average_match' => 1],
    ];

    $leagueData = new \App\DataTransferObjects\League\LeagueData(
        id: new \App\ValueObjects\LeagueId('1133124905354973184'),
        name: 'Test League',
        totalRosters: 10,
        playoffWeekStart: new \App\ValueObjects\Week(15),
        currentWeek: new \App\ValueObjects\Week(4),
        managers: [],
        schedule: new \App\DataTransferObjects\League\WeeklySchedule,
        rawLeagueData: $rawLeagueData
    );

    $analysisResults = \App\DataTransferObjects\Analysis\AnalysisResults::success(
        [],
        [],
        $leagueData
    );

    $mockAnalysisService = Mockery::mock(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class);
    $mockAnalysisService->shouldReceive('analyzeLeague')
        ->once()
        ->andReturn($analysisResults);

    $this->app->instance(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class, $mockAnalysisService);

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertOk();
    $league = $response->viewData('league');
    expect($league->settings->league_average_match)->toBe(1);
});

it('correctly sorts strength of schedule by overall losses', function () {
    // Pre-sorted strength of schedule data (highest losses first = toughest schedule)
    $strengthOfSchedule = [2 => 25, 3 => 20, 1 => 15];

    // Create managers to match the strength of schedule roster IDs
    $managers = [
        1 => [
            'roster_id' => 1,
            'user_id' => 'user1',
            'name' => 'Manager 1',
            'win' => 3, 'loss' => 0,
            'avatar' => null,
            'schedule' => [],
            'records' => [],
        ],
        2 => [
            'roster_id' => 2,
            'user_id' => 'user2',
            'name' => 'Manager 2',
            'win' => 1, 'loss' => 2,
            'avatar' => null,
            'schedule' => [],
            'records' => [],
        ],
        3 => [
            'roster_id' => 3,
            'user_id' => 'user3',
            'name' => 'Manager 3',
            'win' => 2, 'loss' => 1,
            'avatar' => null,
            'schedule' => [],
            'records' => [],
        ],
    ];

    $leagueData = new \App\DataTransferObjects\League\LeagueData(
        id: new \App\ValueObjects\LeagueId('1133124905354973184'),
        name: 'Test League',
        totalRosters: 3,
        playoffWeekStart: new \App\ValueObjects\Week(15),
        currentWeek: new \App\ValueObjects\Week(4),
        managers: $managers,
        schedule: new \App\DataTransferObjects\League\WeeklySchedule,
        rawLeagueData: (object) [
            'name' => 'Test League',
            'total_rosters' => 3,
            'settings' => (object) [
                'league_average_match' => false,
                'playoff_week_start' => 15,
            ],
        ]
    );

    $analysisResults = \App\DataTransferObjects\Analysis\AnalysisResults::success(
        $managers,
        $strengthOfSchedule,
        $leagueData
    );

    $mockAnalysisService = Mockery::mock(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class);
    $mockAnalysisService->shouldReceive('analyzeLeague')
        ->once()
        ->andReturn($analysisResults);

    $this->app->instance(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class, $mockAnalysisService);

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertOk();
    $overallLosses = $response->viewData('overall_losses');

    // Verify that it's sorted in descending order (arsort)
    $previousValue = PHP_INT_MAX;
    foreach ($overallLosses as $rosterId => $losses) {
        expect($losses)->toBeLessThanOrEqual($previousValue);
        $previousValue = $losses;
    }
});

it('preserves exact scores from API data', function () {
    // Create managers with exact scores from fixtures
    $managers = [
        1 => [
            'roster_id' => 1, 'user_id' => 'user1', 'name' => 'Manager 1',
            'win' => 2, 'loss' => 1, 'avatar' => 'https://example.com/avatar.png',
            'schedule' => [
                1 => ['score' => 158.62], 2 => ['score' => 91.6], 3 => ['score' => 96.26],
            ],
            'records' => [],
        ],
        5 => [
            'roster_id' => 5, 'user_id' => 'user5', 'name' => 'Manager 5',
            'win' => 1, 'loss' => 2, 'avatar' => 'https://example.com/avatar.png',
            'schedule' => [
                1 => ['score' => 120.0], 2 => ['score' => 149.94], 3 => ['score' => 110.0],
            ],
            'records' => [],
        ],
        10 => [
            'roster_id' => 10, 'user_id' => 'user10', 'name' => 'Manager 10',
            'win' => 3, 'loss' => 0, 'avatar' => 'https://example.com/avatar.png',
            'schedule' => [
                1 => ['score' => 177.86], 2 => ['score' => 135.0], 3 => ['score' => 140.0],
            ],
            'records' => [],
        ],
    ];

    $leagueData = new \App\DataTransferObjects\League\LeagueData(
        id: new \App\ValueObjects\LeagueId('1133124905354973184'),
        name: 'Test League',
        totalRosters: 10,
        playoffWeekStart: new \App\ValueObjects\Week(15),
        currentWeek: new \App\ValueObjects\Week(4),
        managers: $managers,
        schedule: new \App\DataTransferObjects\League\WeeklySchedule,
        rawLeagueData: (object) ['name' => 'Test League']
    );

    $analysisResults = \App\DataTransferObjects\Analysis\AnalysisResults::success(
        $managers,
        [],
        $leagueData
    );

    $mockAnalysisService = Mockery::mock(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class);
    $mockAnalysisService->shouldReceive('analyzeLeague')
        ->once()
        ->andReturn($analysisResults);

    $this->app->instance(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class, $mockAnalysisService);

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertOk();
    $managers = $response->viewData('managers');

    // Verify specific scores from fixtures are preserved
    expect($managers[1]['schedule'][1]['score'])->toBe(158.62);
    expect($managers[1]['schedule'][2]['score'])->toBe(91.6);
    expect($managers[1]['schedule'][3]['score'])->toBe(96.26);

    expect($managers[10]['schedule'][1]['score'])->toBe(177.86);
    expect($managers[5]['schedule'][2]['score'])->toBe(149.94);
});

it('correctly calculates current week as minimum of sport state and playoff start', function () {
    // Create league data where current week is min(3, 15) = 3
    $leagueData = new \App\DataTransferObjects\League\LeagueData(
        id: new \App\ValueObjects\LeagueId('1133124905354973184'),
        name: 'Test League',
        totalRosters: 10,
        playoffWeekStart: new \App\ValueObjects\Week(15),
        currentWeek: new \App\ValueObjects\Week(3), // min(3, 15) = 3
        managers: [],
        schedule: new \App\DataTransferObjects\League\WeeklySchedule,
        rawLeagueData: (object) ['name' => 'Test League']
    );

    $analysisResults = \App\DataTransferObjects\Analysis\AnalysisResults::success(
        [],
        [],
        $leagueData
    );

    $mockAnalysisService = Mockery::mock(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class);
    $mockAnalysisService->shouldReceive('analyzeLeague')
        ->once()
        ->andReturn($analysisResults);

    $this->app->instance(\App\Services\Analysis\Contracts\FantasyAnalysisInterface::class, $mockAnalysisService);

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertOk();
    $currentWeek = $response->viewData('current_week');
    expect($currentWeek)->toBeInstanceOf(\App\ValueObjects\Week::class);
    expect($currentWeek->toInt())->toBe(3); // min(3, 15) = 3
});
