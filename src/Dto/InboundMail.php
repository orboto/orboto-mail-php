<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class InboundMail
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
        );
    }
}
