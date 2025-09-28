# Refactoring Plan: Sleeper Tools - Best Practices Architecture

## Overview

This document outlines a comprehensive refactoring strategy to transform the current monolithic controller into a maintainable, testable, and extensible architecture following Laravel best practices. The goal is to create a foundation that supports future tools while maintaining clean separation of concerns.

## Current State Analysis

### Problems with Current Implementation
1. **Monolithic Controller**: 300+ lines of complex business logic in a single method
2. **Multiple Responsibilities**: API calls, data transformation, calculations, and view preparation all in one place
3. **Hard to Test**: Tightly coupled code makes unit testing difficult
4. **Not Reusable**: Logic is buried in controller, can't be used elsewhere
5. **Difficult to Extend**: Adding new features requires modifying existing complex code
6. **Error Handling**: Mixed throughout business logic instead of centralized

## Proposed Architecture

### Service Layer Architecture

```
app/
├── Http/
│   └── Controllers/
│       └── ShouldaCouldaWoulda/
│           └── SelectLeagueController.php (simplified)
├── Services/
│   ├── Sleeper/
│   │   ├── SleeperApiService.php
│   │   ├── LeagueDataService.php
│   │   └── Contracts/
│   │       └── SleeperApiInterface.php
│   ├── Fantasy/
│   │   ├── ScheduleAnalysisService.php
│   │   ├── AlternativeRecordsService.php
│   │   ├── StrengthOfScheduleService.php
│   │   └── Contracts/
│   │       ├── ScheduleAnalysisInterface.php
│   │       └── RecordsCalculatorInterface.php
│   └── Analysis/
│       ├── ShouldaCouldaWouldaService.php
│       └── Contracts/
│           └── FantasyAnalysisInterface.php
├── DataTransferObjects/
│   ├── League/
│   │   ├── LeagueData.php
│   │   ├── ManagerData.php
│   │   ├── MatchupData.php
│   │   └── WeeklySchedule.php
│   └── Analysis/
│       ├── AlternativeRecords.php
│       ├── StrengthOfSchedule.php
│       └── AnalysisResults.php
├── ValueObjects/
│   ├── LeagueId.php
│   ├── RosterId.php
│   ├── Week.php
│   └── Score.php
├── Exceptions/
│   ├── SleeperApi/
│   │   ├── InvalidLeagueException.php
│   │   ├── ApiConnectionException.php
│   │   └── InsufficientDataException.php
│   └── Analysis/
│       └── CalculationException.php
└── Repositories/
    ├── Sleeper/
    │   ├── SleeperLeagueRepository.php
    │   └── Contracts/
    │       └── LeagueRepositoryInterface.php
    └── Cache/
        └── CachedSleeperRepository.php
```

## Detailed Refactoring Plan

### Phase 1: Data Transfer Objects (DTOs)

**Purpose**: Create strongly-typed data containers to replace associative arrays

#### Core DTOs

```php
// app/DataTransferObjects/League/LeagueData.php
class LeagueData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $totalRosters,
        public readonly int $playoffWeekStart,
        public readonly array $managers,
        public readonly array $schedule
    ) {}
}

// app/DataTransferObjects/League/ManagerData.php
class ManagerData
{
    public function __construct(
        public readonly RosterId $rosterId,
        public readonly string $userId,
        public readonly string $name,
        public readonly ?string $avatar,
        public readonly int $wins,
        public readonly int $losses,
        public readonly array $schedule,
        public readonly array $records
    ) {}
}

// app/DataTransferObjects/Analysis/AnalysisResults.php
class AnalysisResults
{
    public function __construct(
        public readonly bool $success,
        public readonly array $managers,
        public readonly array $strengthOfSchedule,
        public readonly ?string $error = null
    ) {}
}
```

### Phase 2: Value Objects

**Purpose**: Ensure type safety and domain validation

```php
// app/ValueObjects/LeagueId.php
class LeagueId
{
    public function __construct(private string $value)
    {
        if (empty($value) || !is_numeric($value)) {
            throw new InvalidArgumentException('League ID must be a non-empty numeric string');
        }
    }

    public function toString(): string
    {
        return $this->value;
    }
}
```

### Phase 3: Service Layer

**Purpose**: Extract business logic into focused, testable services

#### API Service Layer
```php
// app/Services/Sleeper/SleeperApiService.php
class SleeperApiService implements SleeperApiInterface
{
    public function getLeague(LeagueId $leagueId): object
    public function getLeagueUsers(LeagueId $leagueId): array
    public function getLeagueRosters(LeagueId $leagueId): array
    public function getLeagueMatchups(LeagueId $leagueId, Week $week): array
    public function getSportState(): object
}

// app/Services/Sleeper/LeagueDataService.php
class LeagueDataService
{
    public function __construct(
        private SleeperApiInterface $sleeperApi,
        private LoggerInterface $logger
    ) {}

    public function getCompleteLeagueData(LeagueId $leagueId): LeagueData
    public function validateLeagueData(LeagueData $league): bool
    public function getCurrentAnalysisWeek(object $state, int $playoffStart): Week
}
```

#### Analysis Service Layer
```php
// app/Services/Fantasy/ScheduleAnalysisService.php
class ScheduleAnalysisService implements ScheduleAnalysisInterface
{
    public function buildScheduleFromMatchups(array $matchups): WeeklySchedule
    public function addSchedulesToManagers(array $managers, WeeklySchedule $schedule): array
    public function initializeRecords(array $managers): array
}

// app/Services/Fantasy/AlternativeRecordsService.php
class AlternativeRecordsService implements RecordsCalculatorInterface
{
    public function calculateAlternativeRecords(array $managers): array
    public function compareManagerAgainstSchedule(ManagerData $manager, ManagerData $scheduleOwner): array
    public function determineMatchupResult(Score $managerScore, Score $opponentScore): bool
}

// app/Services/Fantasy/StrengthOfScheduleService.php
class StrengthOfScheduleService
{
    public function calculateOverallWinsLosses(array $managers): array
    public function rankByStrengthOfSchedule(array $overallLosses): array
    public function generateStrengthAnalysis(array $managers): StrengthOfSchedule
}
```

#### Orchestration Service
```php
// app/Services/Analysis/ShouldaCouldaWouldaService.php
class ShouldaCouldaWouldaService implements FantasyAnalysisInterface
{
    public function __construct(
        private LeagueDataService $leagueData,
        private ScheduleAnalysisService $scheduleAnalysis,
        private AlternativeRecordsService $recordsCalculator,
        private StrengthOfScheduleService $strengthCalculator,
        private LoggerInterface $logger
    ) {}

    public function analyzeLeague(LeagueId $leagueId): AnalysisResults
    {
        try {
            $this->logger->info('Starting Shoulda Coulda Woulda analysis', ['league_id' => $leagueId->toString()]);

            // Phase 1: Get league data
            $leagueData = $this->leagueData->getCompleteLeagueData($leagueId);

            // Phase 2: Build schedules
            $schedule = $this->scheduleAnalysis->buildScheduleFromMatchups($leagueData->matchups);
            $managers = $this->scheduleAnalysis->addSchedulesToManagers($leagueData->managers, $schedule);

            // Phase 3: Calculate alternative records
            $managersWithRecords = $this->recordsCalculator->calculateAlternativeRecords($managers);

            // Phase 4: Calculate strength of schedule
            $strengthOfSchedule = $this->strengthCalculator->generateStrengthAnalysis($managersWithRecords);

            $this->logger->info('Analysis completed successfully');

            return new AnalysisResults(true, $managersWithRecords, $strengthOfSchedule);

        } catch (Exception $e) {
            $this->logger->error('Analysis failed', ['error' => $e->getMessage()]);
            return new AnalysisResults(false, [], [], $e->getMessage());
        }
    }
}
```

### Phase 4: Simplified Controller

**Purpose**: Thin controller focused only on HTTP concerns

```php
// app/Http/Controllers/ShouldaCouldaWoulda/SelectLeagueController.php
class SelectLeagueController extends Controller
{
    public function __construct(
        private ShouldaCouldaWouldaService $analysisService
    ) {}

    public function __invoke(SelectLeagueRequest $request)
    {
        if (!$request->has('league')) {
            return view('shoulda-coulda-woulda.league-select', [
                'valid_league' => false,
                'managers' => [],
                'overall_losses' => [],
                'league' => null,
                'current_week' => null
            ]);
        }

        try {
            $leagueId = new LeagueId($request->league);
            $results = $this->analysisService->analyzeLeague($leagueId);

            if (!$results->success) {
                return redirect()
                    ->route('shoulda-coulda-woulda.select-league')
                    ->with('error', $results->error);
            }

            return view('shoulda-coulda-woulda.league-select', [
                'valid_league' => true,
                'managers' => $results->managers,
                'overall_losses' => $results->strengthOfSchedule->overallLosses,
                'league' => $results->league,
                'current_week' => $results->currentWeek
            ]);

        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('shoulda-coulda-woulda.select-league')
                ->with('error', 'Invalid league ID provided');
        }
    }
}
```

## Testing Strategy

### Unit Tests Structure

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── Sleeper/
│   │   │   ├── SleeperApiServiceTest.php
│   │   │   └── LeagueDataServiceTest.php
│   │   ├── Fantasy/
│   │   │   ├── ScheduleAnalysisServiceTest.php
│   │   │   ├── AlternativeRecordsServiceTest.php
│   │   │   └── StrengthOfScheduleServiceTest.php
│   │   └── Analysis/
│   │       └── ShouldaCouldaWouldaServiceTest.php
│   ├── ValueObjects/
│   │   ├── LeagueIdTest.php
│   │   ├── RosterIdTest.php
│   │   └── ScoreTest.php
│   └── DataTransferObjects/
│       ├── LeagueDataTest.php
│       └── ManagerDataTest.php
├── Feature/
│   ├── ShouldaCouldaWoulda/
│   │   ├── SelectLeagueTest.php
│   │   └── AnalysisIntegrationTest.php
│   └── Api/
│       └── SleeperApiIntegrationTest.php
└── Fixtures/
    ├── league-data.json
    ├── matchups-week-1.json
    └── sample-analysis-results.json
```

### Test Examples

#### Unit Test Example
```php
// tests/Unit/Services/Fantasy/AlternativeRecordsServiceTest.php
class AlternativeRecordsServiceTest extends TestCase
{
    private AlternativeRecordsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AlternativeRecordsService();
    }

    /** @test */
    public function it_calculates_win_when_manager_score_is_higher()
    {
        $managerScore = new Score(150.5);
        $opponentScore = new Score(120.3);

        $result = $this->service->determineMatchupResult($managerScore, $opponentScore);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_direct_matchup_correctly()
    {
        $manager = ManagerDataFactory::create(['roster_id' => 1]);
        $scheduleOwner = ManagerDataFactory::create(['roster_id' => 2]);

        // Add matchup where manager played scheduleOwner directly
        $results = $this->service->compareManagerAgainstSchedule($manager, $scheduleOwner);

        $this->assertArrayHasKey('direct_matchups', $results);
        $this->assertArrayHasKey('alternative_matchups', $results);
    }
}
```

#### Feature Test Example
```php
// tests/Feature/ShouldaCouldaWoulda/SelectLeagueTest.php
class SelectLeagueTest extends TestCase
{
    /** @test */
    public function it_displays_analysis_for_valid_league()
    {
        // Mock the API responses
        SleeperApi::fake([
            'league/123' => SleeperApiResponse::fixture('league-data.json'),
            'league/123/users' => SleeperApiResponse::fixture('users-data.json'),
            // ... other endpoints
        ]);

        $response = $this->get('/shoulda-coulda-woulda?league=123');

        $response->assertOk();
        $response->assertViewHas('valid_league', true);
        $response->assertViewHas('managers');
        $response->assertSee('Strength of Schedule');
    }

    /** @test */
    public function it_handles_invalid_league_gracefully()
    {
        SleeperApi::fake(['league/invalid' => SleeperApiResponse::error(404)]);

        $response = $this->get('/shoulda-coulda-woulda?league=invalid');

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This is not a valid Sleeper league ID!');
    }
}
```

## Benefits of This Architecture

### Maintainability
- **Single Responsibility**: Each class has one clear purpose
- **Dependency Injection**: Easy to swap implementations
- **Separation of Concerns**: HTTP, business logic, and data access are separate

### Testability
- **Unit Testing**: Each service can be tested in isolation
- **Mocking**: Easy to mock dependencies for focused tests
- **Feature Testing**: End-to-end testing of complete workflows

### Reusability
- **Service Composition**: Services can be used in different combinations
- **API Endpoints**: Same services can power API endpoints
- **Background Jobs**: Analysis can be run asynchronously
- **Command Line**: Artisan commands can use the same logic

### Extensibility
- **New Tools**: Additional fantasy analysis tools can reuse existing services
- **Different Sports**: Architecture supports other sports with minimal changes
- **Multiple Data Sources**: Can integrate other fantasy platforms
- **Caching**: Easy to add caching layers without changing business logic

## Migration Strategy

### Step 1: Create Foundation (Week 1)
1. Create DTOs and Value Objects
2. Create service interfaces/contracts
3. Add basic exception classes

### Step 2: Extract API Layer (Week 2)
1. Create SleeperApiService
2. Create LeagueDataService
3. Add comprehensive logging
4. Write unit tests for API layer

### Step 3: Extract Analysis Logic (Week 3)
1. Create ScheduleAnalysisService
2. Create AlternativeRecordsService
3. Create StrengthOfScheduleService
4. Write unit tests for each service

### Step 4: Create Orchestration (Week 4)
1. Create ShouldaCouldaWouldaService
2. Refactor controller
3. Add integration tests
4. Performance optimization

### Step 5: Documentation and Polish (Week 5)
1. Complete test coverage
2. Performance benchmarking
3. Documentation updates
4. Code review and refinement

## Future Enhancements Enabled

With this architecture, future tools become much easier to implement:

- **Power Rankings**: Reuse AlternativeRecordsService with different algorithms
- **Trade Analyzer**: Combine with roster data and projection services
- **Playoff Predictor**: Build on strength of schedule calculations
- **Weekly Insights**: Use schedule analysis for matchup previews
- **Historical Analysis**: Same patterns for multi-season data
- **Real-time Updates**: Services can be used in background jobs
- **API Endpoints**: Expose analysis via REST/GraphQL APIs