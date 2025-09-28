<?php

use HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Clear logs before each test
    Log::shouldReceive('info')->andReturn(null);
    Log::shouldReceive('debug')->andReturn(null);
    Log::shouldReceive('warning')->andReturn(null);
    Log::shouldReceive('error')->andReturn(null);
});

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
    // Mock all the Sleeper API calls with our fixture data
    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->with('1133124905354973184')
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/league-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueUsers')
        ->once()
        ->with('1133124905354973184')
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/users-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueRosters')
        ->once()
        ->with('1133124905354973184')
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/rosters-data.json'), false));

    LaravelSleeper::shouldReceive('getSportState')
        ->once()
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/sport-state.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 1)
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-1.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 2)
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-2.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 3)
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-3.json'), false));

    LaravelSleeper::shouldReceive('showAvatar')
        ->andReturn('https://example.com/avatar.png');

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertOk();
    $response->assertViewIs('shoulda-coulda-woulda.league-select');
    $response->assertViewHas('valid_league', true);

    // Verify managers data structure
    $managers = $response->viewData('managers');
    expect($managers)->toBeArray()
        ->and(count($managers))->toBe(10);

    // Check that each manager has the required structure
    foreach ($managers as $rosterId => $manager) {
        expect($manager)->toHaveKeys([
            'roster_id', 'user_id', 'win', 'loss', 'name', 'avatar', 'schedule', 'records'
        ]);

        expect($manager['schedule'])->toBeArray()
            ->and(count($manager['schedule']))->toBe(3); // 3 weeks of data

        expect($manager['records'])->toBeArray()
            ->and(count($manager['records']))->toBe(10); // Records against all 10 teams
    }

    // Verify overall losses data
    $overallLosses = $response->viewData('overall_losses');
    expect($overallLosses)->toBeArray()
        ->and(count($overallLosses))->toBe(10);

    // Verify league data
    $league = $response->viewData('league');
    expect($league)->toBeObject()
        ->and($league->name)->toBe('Test League');

    // Verify current week
    $currentWeek = $response->viewData('current_week');
    expect($currentWeek)->toBe(4); // min(4, 15) from fixtures
});

it('handles invalid league ID gracefully', function () {
    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->with('invalid')
        ->andThrow(new Exception('League not found'));

    $response = $this->get('/shoulda-coulda-woulda?league=invalid');

    $response->assertRedirect(route('shoulda-coulda-woulda.select-league'));
    $response->assertSessionHas('error', 'This is not a valid Sleeper league ID!');
});

it('handles API connection errors gracefully', function () {
    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->with('1133124905354973184')
        ->andThrow(new Exception('Connection timeout'));

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertRedirect(route('shoulda-coulda-woulda.select-league'));
    $response->assertSessionHas('error', 'This is not a valid Sleeper league ID!');
});

it('handles missing league data gracefully', function () {
    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->with('1133124905354973184')
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/league-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueUsers')
        ->once()
        ->with('1133124905354973184')
        ->andReturn([]); // Empty users - this will cause $users[0] to throw an exception

    LaravelSleeper::shouldReceive('getLeagueRosters')
        ->once()
        ->with('1133124905354973184')
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/rosters-data.json'), false));

    LaravelSleeper::shouldReceive('getSportState')
        ->once()
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/sport-state.json'), false));

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertRedirect(route('shoulda-coulda-woulda.select-league'));
    // The actual behavior: empty users array causes $users[0] to throw exception,
    // which is caught by outer try-catch and returns the generic league ID error
    $response->assertSessionHas('error', 'This is not a valid Sleeper league ID!');
});

it('handles missing league data validation properly', function () {
    // Create a scenario where data validation actually fails (not an exception)
    $sportState = (object) ['week' => 0, 'season' => '2024']; // week 0 will cause current_week to be 0

    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->with('1133124905354973184')
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/league-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueUsers')
        ->once()
        ->with('1133124905354973184')
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/users-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueRosters')
        ->once()
        ->with('1133124905354973184')
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/rosters-data.json'), false));

    LaravelSleeper::shouldReceive('getSportState')
        ->once()
        ->andReturn($sportState);

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertRedirect(route('shoulda-coulda-woulda.select-league'));
    $response->assertSessionHas('error', 'Could not retrieve your league settings!');
});

it('handles matchup fetching errors gracefully', function () {
    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->with('1133124905354973184')
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/league-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueUsers')
        ->once()
        ->with('1133124905354973184')
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/users-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueRosters')
        ->once()
        ->with('1133124905354973184')
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/rosters-data.json'), false));

    LaravelSleeper::shouldReceive('getSportState')
        ->once()
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/sport-state.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 1)
        ->andThrow(new Exception('Matchup fetch failed'));

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertRedirect(route('shoulda-coulda-woulda.select-league'));
    $response->assertSessionHas('error', 'There was an error fetching matchups!');
});

it('correctly calculates alternative records for specific scenarios', function () {
    // Mock API calls
    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/league-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueUsers')
        ->once()
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/users-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueRosters')
        ->once()
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/rosters-data.json'), false));

    LaravelSleeper::shouldReceive('getSportState')
        ->once()
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/sport-state.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 1)
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-1.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 2)
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-2.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 3)
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-3.json'), false));

    LaravelSleeper::shouldReceive('showAvatar')
        ->andReturn('https://example.com/avatar.png');

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

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
    // Create modified league data with league_average_match = 1
    $leagueData = json_decode(file_get_contents(__DIR__ . '/../../Fixtures/league-data.json'), false);
    $leagueData->settings->league_average_match = 1;

    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->andReturn($leagueData);

    LaravelSleeper::shouldReceive('getLeagueUsers')
        ->once()
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/users-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueRosters')
        ->once()
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/rosters-data.json'), false));

    LaravelSleeper::shouldReceive('getSportState')
        ->once()
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/sport-state.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-1.json'), false));

    LaravelSleeper::shouldReceive('showAvatar')
        ->andReturn('https://example.com/avatar.png');

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $response->assertOk();
    $league = $response->viewData('league');
    expect($league->settings->league_average_match)->toBe(1);
});

it('correctly sorts strength of schedule by overall losses', function () {
    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/league-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueUsers')
        ->once()
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/users-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueRosters')
        ->once()
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/rosters-data.json'), false));

    LaravelSleeper::shouldReceive('getSportState')
        ->once()
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/sport-state.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-1.json'), false));

    LaravelSleeper::shouldReceive('showAvatar')
        ->andReturn('https://example.com/avatar.png');

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $overallLosses = $response->viewData('overall_losses');

    // Verify that it's sorted in descending order (arsort)
    $previousValue = PHP_INT_MAX;
    foreach ($overallLosses as $rosterId => $losses) {
        expect($losses)->toBeLessThanOrEqual($previousValue);
        $previousValue = $losses;
    }
});

it('preserves exact scores from API data', function () {
    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/league-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueUsers')
        ->once()
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/users-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueRosters')
        ->once()
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/rosters-data.json'), false));

    LaravelSleeper::shouldReceive('getSportState')
        ->once()
        ->andReturn((object) json_decode(file_get_contents(__DIR__ . '/../../Fixtures/sport-state.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 1)
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-1.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 2)
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-2.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 3)
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-3.json'), false));

    LaravelSleeper::shouldReceive('showAvatar')
        ->andReturn('https://example.com/avatar.png');

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $managers = $response->viewData('managers');

    // Verify specific scores from fixtures are preserved
    expect($managers[1]['schedule'][1]['score'])->toBe(158.62);
    expect($managers[1]['schedule'][2]['score'])->toBe(91.6);
    expect($managers[1]['schedule'][3]['score'])->toBe(96.26);

    expect($managers[10]['schedule'][1]['score'])->toBe(177.86);
    expect($managers[5]['schedule'][2]['score'])->toBe(149.94);
});

it('correctly calculates current week as minimum of sport state and playoff start', function () {
    // Test case where sport state week is less than playoff start
    $sportState = (object) ['week' => 3, 'season' => '2024'];
    $leagueData = json_decode(file_get_contents(__DIR__ . '/../../Fixtures/league-data.json'), false);
    $leagueData->settings->playoff_week_start = 15;

    LaravelSleeper::shouldReceive('getLeague')
        ->once()
        ->andReturn($leagueData);

    LaravelSleeper::shouldReceive('getSportState')
        ->once()
        ->andReturn($sportState);

    LaravelSleeper::shouldReceive('getLeagueUsers')
        ->once()
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/users-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueRosters')
        ->once()
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/rosters-data.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 1)
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-1.json'), false));

    LaravelSleeper::shouldReceive('getLeagueMatchups')
        ->with('1133124905354973184', 2)
        ->andReturn(json_decode(file_get_contents(__DIR__ . '/../../Fixtures/matchups-week-2.json'), false));

    LaravelSleeper::shouldReceive('showAvatar')
        ->andReturn('https://example.com/avatar.png');

    $response = $this->get('/shoulda-coulda-woulda?league=1133124905354973184');

    $currentWeek = $response->viewData('current_week');
    expect($currentWeek)->toBe(3); // min(3, 15) = 3
});