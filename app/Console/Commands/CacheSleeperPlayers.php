<?php

namespace App\Console\Commands;

use HOTSAUCEJAKE\LaravelSleeper\Facades\LaravelSleeper;
use Illuminate\Console\Command;

class CacheSleeperPlayers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sleeper:cache-players {--force : Force refresh even if cache exists}';

    /**
     * The console command description.
     */
    protected $description = 'Cache Sleeper player data (recommended weekly due to large dataset ~5MB)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏈 Caching Sleeper player data...');

        $cacheKey = 'sleeper_players_nfl';
        $force = $this->option('force');

        if ($force) {
            cache()->forget($cacheKey);
            $this->info('Cleared existing cache.');
        }

        if (cache()->has($cacheKey) && ! $force) {
            $this->info('Player data already cached. Use --force to refresh.');

            return self::SUCCESS;
        }

        try {
            $startTime = microtime(true);

            $playerData = cache()->remember($cacheKey, now()->addDays(7), function () {
                $this->info('Fetching player data from Sleeper API...');

                return LaravelSleeper::getAllPlayers('nfl');
            });

            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);

            $playerCount = is_object($playerData) ? count(get_object_vars($playerData)) : count($playerData);

            $this->info("✅ Successfully cached {$playerCount} players in {$executionTime}ms");
            $this->info('Cache expires: '.now()->addDays(7)->format('Y-m-d H:i:s'));

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to cache player data: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
