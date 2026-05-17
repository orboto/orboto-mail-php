<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class SendBatchItemResult
{
    public function __construct(
        public int $index,
        public bool $ok,
        /** Success-only — SES-issued message-id. */
        public ?string $messageId = null,
        /** Success-only — always `queued`. */
        public ?string $status = null,
        /** Success-only — true when this item consumed an over-base-quota slot. */
        public ?bool $overage = null,
        /** Error-only — error code (e.g. `quota_exhausted_daily`). */
        public ?string $error = null,
        public ?string $reason = null,
        public ?string $message = null,
        /** Set when an earlier item exhausted quota and this one wasn't attempted. */
        public bool $quotaSkipped = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            index: (int) $data['index'],
            ok: (bool) $data['ok'],
            messageId: isset($data['messageId']) ? (string) $data['messageId'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            overage: isset($data['overage']) ? (bool) $data['overage'] : null,
            error: isset($data['error']) ? (string) $data['error'] : null,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
            message: isset($data['message']) ? (string) $data['message'] : null,
            quotaSkipped: (bool) ($data['quotaSkipped'] ?? false),
        );
    }
}
