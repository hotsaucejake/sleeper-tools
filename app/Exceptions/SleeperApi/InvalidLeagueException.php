<?php

namespace App\Exceptions\SleeperApi;

use Exception;
use App\ValueObjects\LeagueId;

class InvalidLeagueException extends Exception
{
    public function __construct(LeagueId $leagueId, ?string $message = null)
    {
        $message = $message ?? "Invalid Sleeper league ID: {$leagueId}";
        parent::__construct($message);
    }
}