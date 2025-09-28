<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class LeagueId
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty($value) || ! is_numeric($value)) {
            throw new InvalidArgumentException('League ID must be a non-empty numeric string');
        }

        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(LeagueId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
