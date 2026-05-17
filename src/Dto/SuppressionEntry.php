<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class SuppressionEntry
{
    public function __construct(
        public string $email,
        /** `hard-bounce` | `complaint` | `manual` */
        public string $reason,
        public string $addedAt,
        public string $addedBy,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: (string) $data['email'],
            reason: (string) $data['reason'],
            addedAt: (string) $data['addedAt'],
            addedBy: (string) $data['addedBy'],
        );
    }
}
