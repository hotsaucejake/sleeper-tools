# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel 12 application providing fantasy football analytics tools for Sleeper leagues. Currently features "Shoulda Coulda Woulda" analysis with plans for comprehensive statistical toolkit expansion.

## Architecture (Service-Oriented Design)

**Service Layer Pattern**: Refactored from monolithic controller to service-based architecture
- **Controllers**: Handle HTTP requests/responses only
- **Services**: Business logic orchestration
- **Value Objects**: Type-safe data containers (LeagueId, Week, RosterId, Score)
- **DTOs**: Structured data transfer between layers
- **Interfaces**: Service contracts for testability

### Core Services
- `ShouldaCouldaWouldaService`: Main analysis orchestration
- `LeagueDataService`: Sleeper API data fetching/validation
- `ScheduleAnalysisService`: Matchup processing and schedule building
- `AlternativeRecordsService`: Core "what-if" calculation engine
- `StrengthOfScheduleService`: Opponent difficulty analysis

## Key Dependencies

- **Laravel**: v12.31+ (PHP 8.2+)
- **Pest PHP**: Testing framework with Mockery for mocking
- **hotsaucejake/laravel-sleeper**: v1.0+ - Sleeper API wrapper
- **Bootstrap 5**: Frontend framework

## Development Commands

```bash
# Development server + queue + logs + vite
composer run dev

# Run tests (use Pest commands)
php artisan test

# Code formatting
vendor/bin/pint

# Asset builds
npm run dev        # Development with hot reload
npm run build      # Production build
```

## Testing Strategy

**Framework**: Pest PHP with comprehensive coverage
- **Unit Tests**: Service layer logic, value objects, DTOs
- **Feature Tests**: Controller integration and end-to-end workflows
- **Mocking**: Sleeper API responses for consistent testing

**Important**: Always run tests after changes - service layer has complex dependencies

## API Integration (Laravel Sleeper Package)

Primary facade: `HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper`

**Key Methods**:
- `getLeague($league_id)` - League settings/metadata
- `getLeagueUsers($league_id)` - User profiles and rosters
- `getLeagueMatchups($league_id, $week)` - Weekly scoring data
- `getSportState()` - Current NFL week/season state
- `showAvatar($avatar_id)` - Avatar URL generation

**Rate Limit**: Stay under 1000 calls/minute

## Current Features

### Shoulda Coulda Woulda Analysis
- Alternative win/loss records if teams played different schedules
- Head-to-head performance matrix
- Strength of schedule rankings
- "Luck vs skill" separation analysis

**Algorithm**: For each team, calculates how they would perform against every other team's actual schedule, revealing scheduling advantages/disadvantages.

## Planned Expansion (TODO.md)

50+ analytics tools planned across categories:
- Performance dashboards, draft analysis, trade intelligence
- Manager behavioral patterns, luck vs skill metrics
- Multi-season career tracking, predictive modeling
- Achievement systems, competitive intelligence

## Environment Setup

```bash
cp .env.example .env
php artisan key:generate
composer install && npm install
php artisan migrate
```

**Docker**: Use `./sail` prefix for all commands when using Laravel Sail