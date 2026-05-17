<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class SendBatchResult
{
    public function __construct(
        /** @var SendBatchItemResult[] */
        public array $results,
        /** Quota snapshot from the LAST processed item. */
        public QuotaState $remainingQuota,
        public SendBatchSummary $summary,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            results: array_map(
                fn (array $r) => SendBatchItemResult::fromArray($r),
                $data['results'] ?? [],
            ),
            remainingQuota: QuotaState::fromArray($data['remainingQuota']),
            summary: SendBatchSummary::fromArray($data['summary']),
        );
    }
}
