# Sleeper Tools

A Laravel-based toolkit for analyzing Sleeper fantasy football leagues with advanced statistics and insights.

## Overview

Sleeper Tools is a web application that provides comprehensive analysis tools for Sleeper fantasy football leagues. Built with Laravel 12 and modern PHP practices, it offers data-driven insights to help fantasy players understand their league dynamics, evaluate team performance, and make strategic decisions.

## Current Tools

### Shoulda Coulda Woulda Analysis

An advanced fantasy football analysis tool that calculates alternative win/loss records and strength of schedule metrics for your Sleeper league.

**What it does:**
- Calculates how each team would perform if they played every other team's schedule
- Analyzes head-to-head matchups between all teams
- Ranks teams by strength of schedule (toughest to easiest opponents)
- Identifies luck factors in current standings

**Key Features:**
- Interactive team cards showing actual vs. alternative records
- Strength of schedule visualization with progress bars
- Head-to-head breakdown for every team combination
- Responsive design for desktop and mobile viewing

For detailed technical documentation, see [shoulda-coulda-woulda.md](./shoulda-coulda-woulda.md).

### Performance Awards

A comprehensive weekly awards system that recognizes outstanding (and not-so-outstanding) performances across your Sleeper league.

**What it does:**
- Analyzes weekly performances and assigns fun awards to managers
- Calculates optimal lineup efficiency to identify best and worst managers
- Tracks cumulative award tallies throughout the season
- Displays detailed player information with avatars and statistics

**Award Categories:**
- **The Money Shot** 💰: Highest scoring player of the week
- **The Taco** 🌮: Manager with the lowest total score
- **Best Manager** 🔥: Highest optimal lineup efficiency percentage
- **Worst Manager** 🤔: Lowest optimal lineup efficiency percentage
- **Biggest Blowout** 😂: Largest margin of victory
- **Narrow Victory** 😱: Smallest winning margin
- **Overachiever** 🤓: Scored highest above projected points
- **Below Expectation** 💀: Scored lowest below projected points
- **Position Awards** ⭐: Best QB, RB, WR, TE, K, and DEF performances
- **Benchwarmer Awards** 👀: Best bench players by position
- **The Ron Jeremy Performance Award** 🍆: Most points scored on the bench

**Key Features:**
- Week-by-week analysis with previous completed week as default
- Position-aware optimal lineup calculations for accurate manager efficiency
- Player avatars and detailed stats from Sleeper's database
- Cumulative award tallies showing season-long performance trends
- Responsive design matching the application's aesthetic

## Planned Tools

This project is designed to expand with additional analysis tools based on league data and statistics:

- **Player Performance Analytics**: Deep dive into individual player consistency and upside
- **Trade Analyzer**: Evaluate trade fairness and projected impact
- **Waiver Wire Intelligence**: Advanced pickup recommendations based on schedule and trends
- **Playoff Predictor**: Monte Carlo simulations for playoff scenarios
- **Draft Analysis**: Post-draft evaluation and keeper league insights

## Technology Stack

- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Testing**: Pest PHP with Mockery
- **Frontend**: Blade templates with Tailwind CSS
- **API Integration**: Sleeper Fantasy API
- **Architecture**: Service-oriented with dependency injection

## Installation

### Requirements

- PHP 8.2 or higher
- Composer
- Node.js and npm
- Docker (optional, for Laravel Sail)

### Setup

1. Clone the repository:
```bash
git clone https://github.com/hotsaucejake/sleeper-tools.git
cd sleeper-tools
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node.js dependencies:
```bash
npm install
```

4. Copy environment file:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Build assets:
```bash
npm run build
```

### Using Laravel Sail (Docker)

If you prefer using Docker:

```bash
# Start the containers
./vendor/bin/sail up -d

# Install dependencies
./vendor/bin/sail composer install
./vendor/bin/sail npm install

# Generate key and build assets
./vendor/bin/sail artisan key:generate
./vendor/bin/sail npm run build
```

## Usage

### Shoulda Coulda Woulda Analysis

1. Navigate to the application in your browser
2. Enter your Sleeper League ID (found in your league URL)
3. Click "Analyze League" to generate comprehensive statistics
4. Explore team cards and strength of schedule rankings

### Performance Awards

1. From the dashboard, click "Performance Awards"
2. Select the week you want to analyze (defaults to most recent completed week)
3. View all weekly awards with detailed player information
4. Check the award tallies table to see cumulative season performance

### Finding Your League ID

Your Sleeper League ID is the numeric value in your league URL:
- Example: `https://sleeper.app/leagues/123456789/matchups`
- League ID: `123456789`

## Testing

The project uses Pest PHP for testing with comprehensive unit and feature test coverage.

### Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test types
php artisan test --group=unit
php artisan test --group=feature

# Using Sail
./vendor/bin/sail artisan test
```

### Test Structure

- **Unit Tests**: Service layer logic, value objects, and DTOs
- **Feature Tests**: Controller integration and end-to-end workflows
- **Mock Data**: Realistic Sleeper API responses for consistent testing

## Architecture

### Service Layer Design

The application follows a service-oriented architecture with clear separation of concerns:

- **Controllers**: Handle HTTP requests and responses
- **Services**: Business logic and orchestration
- **Value Objects**: Type-safe data containers (LeagueId, Week, etc.)
- **DTOs**: Structured data transfer between layers
- **Interfaces**: Contracts for external API integration

### Key Services

- `ShouldaCouldaWouldaService`: Main analysis orchestration
- `PerformanceAwardsService`: Weekly award calculations and optimal lineup analysis
- `LeagueDataService`: Sleeper API data fetching and validation
- `ScheduleAnalysisService`: Matchup processing and schedule building
- `AlternativeRecordsService`: Core "what-if" calculation engine
- `StrengthOfScheduleService`: Opponent difficulty analysis

## API Integration

The application integrates with the Sleeper Fantasy API to fetch:
- League settings and metadata
- User profiles and roster information
- Weekly matchup results and scores
- Current NFL season state

All API interactions are abstracted through service contracts for testability and maintainability.

## Contributing

Contributions are welcome! Please feel free to submit issues, feature requests, or pull requests.

### Development Guidelines

- Follow PSR-12 coding standards
- Write tests for new features
- Use type hints and return types
- Document complex algorithms
- Maintain service layer separation

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Acknowledgments

- [Sleeper](https://sleeper.app) for providing the fantasy football API
- The Laravel community for excellent documentation and packages
- Fantasy football enthusiasts who inspired these analytical tools