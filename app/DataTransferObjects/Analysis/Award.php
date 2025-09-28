<?php

namespace App\DataTransferObjects\Analysis;

class Award
{
    public function __construct(
        public readonly string $title,
        public readonly string $emoji,
        public readonly string $managerName,
        public readonly string $description,
        public readonly float $value,
        public readonly ?string $secondaryManagerName = null,
        public readonly ?array $playerInfo = null
    ) {}
}
