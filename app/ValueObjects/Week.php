<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class Week
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 1 || $value > 22) {
            throw new InvalidArgumentException('Week must be between 1 and 22');
        }

        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function equals(Week $other): bool
    {
        return $this->value === $other->value;
    }

    public function isRegularSeason(): bool
    {
        return $this->value <= 18;
    }

    public function isPlayoffs(): bool
    {
        return $this->value > 18;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
