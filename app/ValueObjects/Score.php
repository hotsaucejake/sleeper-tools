<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class Score
{
    private float $value;

    public function __construct(float $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Score cannot be negative');
        }

        $this->value = $value;
    }

    public function toFloat(): float
    {
        return $this->value;
    }

    public function isGreaterThan(Score $other): bool
    {
        return $this->value > $other->value;
    }

    public function isLessThan(Score $other): bool
    {
        return $this->value < $other->value;
    }

    public function equals(Score $other): bool
    {
        return abs($this->value - $other->value) < 0.001; // Handle floating point precision
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}