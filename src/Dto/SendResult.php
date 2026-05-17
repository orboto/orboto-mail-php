<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class SendResult
{
    public function __construct(
        /** SES-issued message-id. Stored on `oms_sends.message_id`. */
        public string $messageId,
        /** `queued` at success-time; later moves through SES events. */
        public string $status,
        /** Quota snapshot AFTER this send was accounted for. */
        public QuotaState $remainingQuota,
        /** True when this send consumed an over-base-quota slot. */
        public bool $overage,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            messageId: (string) $data['messageId'],
            status: (string) $data['status'],
            remainingQuota: QuotaState::fromArray($data['remainingQuota']),
            overage: (bool) ($data['overage'] ?? false),
        );
    }
}
