<?php

namespace App\DataTransferObjects\League;

use App\ValueObjects\RosterId;

class ManagerData
{
    public function __construct(
        public readonly RosterId $rosterId,
        public readonly string $userId,
        public readonly string $name,
        public readonly ?string $avatar,
        public readonly int $wins,
        public readonly int $losses,
        public readonly array $schedule = [],
        public readonly array $records = []
    ) {}

    public function toArray(): array
    {
        return [
            'roster_id' => $this->rosterId->toInt(),
            'user_id' => $this->userId,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'win' => $this->wins,
            'loss' => $this->losses,
            'schedule' => $this->schedule,
            'records' => $this->records,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            rosterId: new RosterId($data['roster_id']),
            userId: $data['user_id'],
            name: $data['name'],
            avatar: $data['avatar'] ?? null,
            wins: $data['win'],
            losses: $data['loss'],
            schedule: $data['schedule'] ?? [],
            records: $data['records'] ?? []
        );
    }

    public function withSchedule(array $schedule): self
    {
        return new self(
            $this->rosterId,
            $this->userId,
            $this->name,
            $this->avatar,
            $this->wins,
            $this->losses,
            $schedule,
            $this->records
        );
    }

    public function withRecords(array $records): self
    {
        return new self(
            $this->rosterId,
            $this->userId,
            $this->name,
            $this->avatar,
            $this->wins,
            $this->losses,
            $this->schedule,
            $records
        );
    }
}