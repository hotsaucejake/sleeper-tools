<?php

namespace App\Services\Analysis\Contracts;

use App\DataTransferObjects\Analysis\AnalysisResults;
use App\ValueObjects\LeagueId;

interface FantasyAnalysisInterface
{
    /**
     * Perform complete Shoulda Coulda Woulda analysis for a league
     */
    public function analyzeLeague(LeagueId $leagueId): AnalysisResults;
}
