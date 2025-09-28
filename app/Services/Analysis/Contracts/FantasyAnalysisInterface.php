<?php

namespace App\Services\Analysis\Contracts;

use App\ValueObjects\LeagueId;
use App\DataTransferObjects\Analysis\AnalysisResults;

interface FantasyAnalysisInterface
{
    /**
     * Perform complete Shoulda Coulda Woulda analysis for a league
     */
    public function analyzeLeague(LeagueId $leagueId): AnalysisResults;
}