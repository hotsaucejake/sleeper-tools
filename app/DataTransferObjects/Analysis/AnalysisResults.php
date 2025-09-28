<?php

namespace App\DataTransferObjects\Analysis;

use App\DataTransferObjects\League\LeagueData;

class AnalysisResults
{
    public function __construct(
        public readonly bool $success,
        public readonly array $managers,
        public readonly array $strengthOfSchedule,
        public readonly ?LeagueData $league = null,
        public readonly ?string $error = null
    ) {}

    public static function success(
        array $managers,
        array $strengthOfSchedule,
        LeagueData $league
    ): self {
        return new self(
            success: true,
            managers: $managers,
            strengthOfSchedule: $strengthOfSchedule,
            league: $league
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            managers: [],
            strengthOfSchedule: [],
            error: $error
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getCurrentWeek(): ?int
    {
        return $this->league?->currentWeek->toInt();
    }
}