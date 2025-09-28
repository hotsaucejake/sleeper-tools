<?php

namespace App\DataTransferObjects\League;

use App\ValueObjects\RosterId;
use App\ValueObjects\Score;
use App\ValueObjects\Week;

class MatchupData
{
    public function __construct(
        public readonly Week $week,
        public readonly RosterId $rosterId,
        public readonly Score $score,
        public readonly RosterId $opponentRosterId,
        public readonly int $matchupId
    ) {}

    public static function fromSleeperData(int $week, object $matchupData, RosterId $opponentRosterId): self
    {
        return new self(
            week: new Week($week),
            rosterId: new RosterId($matchupData->roster_id),
            score: new Score($matchupData->points),
            opponentRosterId: $opponentRosterId,
            matchupId: $matchupData->matchup_id
        );
    }

    public function toArray(): array
    {
        return [
            'week' => $this->week->toInt(),
            'roster_id' => $this->rosterId->toInt(),
            'score' => $this->score->toFloat(),
            'vs' => $this->opponentRosterId->toInt(),
            'matchup_id' => $this->matchupId,
        ];
    }
}
