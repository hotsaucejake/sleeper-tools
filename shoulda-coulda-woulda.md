# Shoulda Coulda Woulda - Documentation

## Overview

The "Shoulda Coulda Woulda" feature is a fantasy football analysis tool that calculates alternative win/loss records for each team in a Sleeper league. It shows how each team would have performed if they had played every other team's schedule, providing insights into strength of schedule and luck factors.

## Core Concept

Instead of showing just the actual win/loss records, this tool calculates:
1. **Head-to-Head Records**: How each team would perform against every other specific team
2. **Alternative Schedule Records**: How each team would perform if they played someone else's schedule
3. **Strength of Schedule Rankings**: Which teams had the toughest/easiest schedules based on opponent performance

## Data Flow and Logic

### Phase 1: Data Collection
1. **League Validation**: Fetch league details using the provided Sleeper League ID
2. **User Data**: Get all league participants and their display names/avatars
3. **Roster Data**: Get team rosters with current win/loss records
4. **Matchup Data**: Fetch all completed weekly matchups up to current week
5. **Sport State**: Determine current NFL week to know how many weeks to analyze

### Phase 2: Data Structure Building

#### Managers Array Structure
```php
$managers[roster_id] = [
    'roster_id' => int,
    'user_id' => string,
    'win' => int,           // Actual wins
    'loss' => int,          // Actual losses
    'name' => string,       // Display name
    'avatar' => string,     // Avatar URL
    'schedule' => [         // Weekly matchups
        week => [
            'score' => float,    // Team's score that week
            'vs' => int,         // Opponent's roster_id
            'roster_id' => int   // This team's roster_id
        ]
    ],
    'records' => [          // Head-to-head vs all teams
        opponent_roster_id => [
            'name' => string,
            'roster_id' => int,
            'win' => int,       // Wins against this specific opponent
            'loss' => int       // Losses against this specific opponent
        ]
    ]
]
```

### Phase 3: Schedule Processing
- **Schedule Building**: For each week, determine who played whom and what scores were achieved
- **Matchup Pairing**: Use `matchup_id` to pair opponents and record scores

### Phase 4: Alternative Record Calculation

This is the core algorithm that answers "What if Team A played Team B's schedule?"

#### For each team (Team A):
1. **Initialize Records**: Create empty win/loss records against every other team
2. **For each opponent team (Team B)**:
   - Look at Team B's actual schedule (who they played each week)
   - Compare Team A's scores from those same weeks against Team B's opponents
   - Record wins/losses for Team A against each of Team B's opponents

#### Algorithm Details:
```php
foreach ($managers as $manager) { // Team A
    foreach ($manager['records'] as $roster => $record) { // Team B's schedule
        foreach ($managers[$roster]['schedule'] as $week => $results) { // Each week of Team B's schedule
            if ($results['vs'] === $manager['roster_id']) {
                // Team A actually played Team B this week - direct comparison
                if ($manager['schedule'][$week]['score'] > $managers[$results['roster_id']]['schedule'][$week]['score']) {
                    $managers[$manager['roster_id']]['records'][$roster]['win']++;
                } else {
                    $managers[$manager['roster_id']]['records'][$roster]['loss']++;
                }
            } else {
                // Team A didn't play Team B this week - compare Team A's score vs Team B's opponent
                if ($manager['schedule'][$week]['score'] > $managers[$results['vs']]['schedule'][$week]['score']) {
                    $managers[$manager['roster_id']]['records'][$roster]['win']++;
                } else {
                    $managers[$manager['roster_id']]['records'][$roster]['loss']++;
                }
            }
        }
    }
}
```

### Phase 5: Strength of Schedule Calculation
1. **Aggregate Losses**: Sum up total losses each team would have accumulated across all possible schedules
2. **Sort by Difficulty**: Teams with more total losses had easier schedules (their opponents scored less)
3. **Ranking**: Display teams ranked from toughest to easiest strength of schedule

## Key Variables Explained

- **`$valid_league`**: Boolean flag indicating if league data was successfully retrieved
- **`$managers`**: Main data structure containing all team information and calculated records
- **`$schedule`**: Intermediate structure organizing weekly matchup data
- **`$overall_wins`**: Array of total wins each team would have across all possible schedules
- **`$overall_losses`**: Array of total losses each team would have across all possible schedules
- **`$matchups`**: Raw weekly matchup data from Sleeper API
- **`$current_week`**: Last completed week to analyze (stops at playoff start)

## Frontend Display

### Strength of Schedule Chart
- Progress bars showing relative difficulty
- Longer bars = tougher schedule (more losses against that schedule)
- Teams ranked from toughest to easiest

### Individual Team Cards
- **Actual Record**: Real wins/losses from the season
- **Head-to-Head Breakdown**: How this team performs against each specific opponent
- **League Average Matching**: Optional adjustment for leagues with median scoring

## Error Handling

1. **Invalid League ID**: Catches API errors and redirects with error message
2. **Matchup Fetch Failures**: Handles cases where weekly matchup data is unavailable
3. **Data Validation**: Ensures users, rosters, and current week data exist before processing

## Use Cases

1. **Luck Analysis**: Identify teams that got lucky/unlucky with their schedule
2. **Playoff Seeding**: Understand if standings reflect true team strength
3. **Trade Evaluation**: See how teams perform against specific opponents
4. **Commissioner Insights**: Analyze league balance and scheduling fairness