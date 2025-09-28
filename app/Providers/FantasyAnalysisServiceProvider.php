<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Sleeper\Contracts\SleeperApiInterface;
use App\Services\Sleeper\SleeperApiService;
use App\Services\Fantasy\Contracts\ScheduleAnalysisInterface;
use App\Services\Fantasy\ScheduleAnalysisService;
use App\Services\Fantasy\Contracts\RecordsCalculatorInterface;
use App\Services\Fantasy\AlternativeRecordsService;
use App\Services\Analysis\Contracts\FantasyAnalysisInterface;
use App\Services\Analysis\ShouldaCouldaWouldaService;

class FantasyAnalysisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind API Layer
        $this->app->bind(SleeperApiInterface::class, SleeperApiService::class);

        // Bind Analysis Services
        $this->app->bind(ScheduleAnalysisInterface::class, ScheduleAnalysisService::class);
        $this->app->bind(RecordsCalculatorInterface::class, AlternativeRecordsService::class);

        // Bind Orchestration Service
        $this->app->bind(FantasyAnalysisInterface::class, ShouldaCouldaWouldaService::class);
    }

    public function boot(): void
    {
        //
    }
}