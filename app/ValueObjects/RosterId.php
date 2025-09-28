<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class RosterId
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('Roster ID must be a positive integer');
        }

        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function equals(RosterId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
