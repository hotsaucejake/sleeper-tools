<?php

namespace App\DataTransferObjects\Analysis;

use App\DataTransferObjects\League\LeagueData;

class PerformanceAwardsResults
{
    public function __construct(
        public readonly bool $success,
        public readonly array $awards,
        public readonly ?LeagueData $league = null,
        public readonly ?int $week = null,
        public readonly ?string $error = null
    ) {}

    public static function success(
        array $awards,
        LeagueData $league,
        int $week
    ): self {
        return new self(
            success: true,
            awards: $awards,
            league: $league,
            week: $week
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            awards: [],
            error: $error
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return ! $this->success;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}