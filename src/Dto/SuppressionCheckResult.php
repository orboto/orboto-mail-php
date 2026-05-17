<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class SuppressionCheckResult
{
    public function __construct(
        public string $email,
        public bool $suppressed,
        public ?SuppressionEntry $entry,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: (string) $data['email'],
            suppressed: (bool) $data['suppressed'],
            entry: isset($data['entry']) ? SuppressionEntry::fromArray($data['entry']) : null,
        );
    }
}
