<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class SendBatchSummary
{
    public function __construct(
        public int $queued,
        public int $rejected,
        public int $suppressed,
        public int $skipped,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            queued: (int) ($data['queued'] ?? 0),
            rejected: (int) ($data['rejected'] ?? 0),
            suppressed: (int) ($data['suppressed'] ?? 0),
            skipped: (int) ($data['skipped'] ?? 0),
        );
    }
}
