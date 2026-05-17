<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

/**
 * One inbound mail + a 15-min presigned-URL for the raw MIME body.
 * Fetch the body from `$downloadUrl` directly — no Bearer needed, the
 * URL is its own credential.
 */
final readonly class InboundDetail
{
    public function __construct(
        public string $id,
        public string $messageId,
        public string $from,
        public string $to,
        public ?string $subject,
        public ?int $sizeBytes,
        public string $parsedStatus,
        public string $receivedAt,
        public string $downloadUrl,
        public string $downloadExpiresAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            messageId: (string) $data['messageId'],
            from: (string) $data['from'],
            to: (string) $data['to'],
            subject: isset($data['subject']) ? (string) $data['subject'] : null,
            sizeBytes: isset($data['sizeBytes']) ? (int) $data['sizeBytes'] : null,
            parsedStatus: (string) $data['parsedStatus'],
            receivedAt: (string) $data['receivedAt'],
            downloadUrl: (string) $data['downloadUrl'],
            downloadExpiresAt: (string) $data['downloadExpiresAt'],
        );
    }
}
