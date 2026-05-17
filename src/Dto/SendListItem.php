<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class SendListItem
{
    public function __construct(
        public string $id,
        public string $fromAddress,
        public string $toAddress,
        public ?string $subject,
        public ?string $messageId,
        /** `queued` | `delivered` | `bounced` | `complained` | `rejected` */
        public string $status,
        public ?string $bounceType,
        public ?string $complaintType,
        public ?string $sesRegion,
        public ?int $sizeBytes,
        public bool $overage,
        public ?array $tags,
        public ?string $templateId,
        public ?string $rejectedReason,
        public string $createdAt,
        public ?string $deliveredAt,
        public ?string $bouncedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            fromAddress: (string) $data['fromAddress'],
            toAddress: (string) $data['toAddress'],
            subject: isset($data['subject']) ? (string) $data['subject'] : null,
            messageId: isset($data['messageId']) ? (string) $data['messageId'] : null,
            status: (string) $data['status'],
            bounceType: isset($data['bounceType']) ? (string) $data['bounceType'] : null,
            complaintType: isset($data['complaintType']) ? (string) $data['complaintType'] : null,
            sesRegion: isset($data['sesRegion']) ? (string) $data['sesRegion'] : null,
            sizeBytes: isset($data['sizeBytes']) ? (int) $data['sizeBytes'] : null,
            overage: (bool) ($data['overage'] ?? false),
            tags: $data['tags'] ?? null,
            templateId: isset($data['templateId']) ? (string) $data['templateId'] : null,
            rejectedReason: isset($data['rejectedReason']) ? (string) $data['rejectedReason'] : null,
            createdAt: (string) $data['createdAt'],
            deliveredAt: isset($data['deliveredAt']) ? (string) $data['deliveredAt'] : null,
            bouncedAt: isset($data['bouncedAt']) ? (string) $data['bouncedAt'] : null,
        );
    }
}
