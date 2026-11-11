<?php

namespace App\Dtos\Issues;

use App\Enums\IssueStatus;

class IssueDto
{
    public function __construct(
        public int $userId,
        public ?string $startedAt = null,
        public ?string $finishedAt = null,
        public IssueStatus $status = IssueStatus::OPEN,
    ) {}

    /** @return array{status: string, user_id: int, started_at: ?string, finished_at: ?string} */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'user_id' => $this->userId,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
        ];
    }
}
