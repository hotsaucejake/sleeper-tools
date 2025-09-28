# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application that provides tools for analyzing Sleeper fantasy football leagues. The primary feature is "Shoulda Coulda Woulda" - a strength of schedule analyzer that shows how teams would perform against different opponents.

## Key Dependencies

- **Laravel Framework**: v12.31+ (PHP 8.2+)
- **hotsaucejake/laravel-sleeper**: v1.0+ - Custom package for Sleeper API integration
- **Laravel Sanctum**: API authentication
- **Laravel UI**: Basic authentication scaffolding
- **Bootstrap 5**: Frontend framework
- **Vite**: Asset bundling

## Development Commands

### Basic Laravel Commands
```bash
# Start development server
php artisan serve

# Full development environment (server + queue + logs + vite)
composer run dev

# Run tests
php artisan test
# or
vendor/bin/phpunit

# Code formatting
vendor/bin/pint

# Queue processing
php artisan queue:work

# View logs in real-time
php artisan pail
```

### Asset Management
```bash
# Development build with hot reload
npm run dev

# Production build
npm run build
```

### Database
```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Reset and reseed
php artisan migrate:fresh --seed
```

## Application Architecture

### Core Components

1. **Sleeper API Integration**: Uses the `hotsaucejake/laravel-sleeper` package to fetch league data, user information, matchups, and calculate alternative standings.

2. **Single Page Application**: The main feature is implemented as a single controller (`SelectLeagueController`) that handles league analysis.

3. **Frontend**: Bootstrap 5-based UI with Blade templates. Primary view is `shoulda-coulda-woulda/league-select.blade.php`.

### Key Files

- `app/Http/Controllers/ShouldaCouldaWoulda/SelectLeagueController.php` - Main logic for fetching and analyzing league data
- `resources/views/shoulda-coulda-woulda/league-select.blade.php` - Primary UI for league analysis
- `resources/views/layouts/shoulda-coulda-woulda.blade.php` - Layout template
- `routes/web.php` - Route definitions

### Data Flow

1. User enters Sleeper League ID
2. Controller fetches league data via LaravelSleeper facade
3. Processes matchup data to calculate alternative records
4. Renders visualization showing strength of schedule and head-to-head records

### Laravel Sleeper Package Usage

The application heavily relies on the `HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper` facade for:
- `getLeague($league_id)` - Fetch league settings
- `getLeagueUsers($league_id)` - Get league participants
- `getLeagueRosters($league_id)` - Get team rosters and records
- `getLeagueMatchups($league_id, $week)` - Get weekly matchup results
- `getSportState()` - Get current NFL week information
- `showAvatar($avatar_id)` - Generate avatar URLs

## Environment Setup

1. Copy `.env.example` to `.env`
2. Set `APP_KEY` with `php artisan key:generate`
3. Configure database connection in `.env`
4. Run `composer install` and `npm install`
5. Run migrations: `php artisan migrate`

## Docker Support

The project includes Docker configuration via Laravel Sail:
```bash
# Start containers
./sail up -d

# Run artisan commands
./sail artisan migrate

# Run npm commands
./sail npm run dev
```

## Testing

- Uses PHPUnit for testing
- Test configuration in `phpunit.xml`
- Test files in `tests/` directory (Unit and Feature)
- Uses in-memory SQLite for testing (`DB_DATABASE=testing`)