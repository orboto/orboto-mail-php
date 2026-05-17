<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class CloudflareAutoSetupResult
{
    public function __construct(
        public bool $ok,
        public string $zoneId,
        public int $recordsCreated,
        public bool $tokenStored,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            ok: (bool) $data['ok'],
            zoneId: (string) $data['zoneId'],
            recordsCreated: (int) $data['recordsCreated'],
            tokenStored: (bool) ($data['tokenStored'] ?? false),
        );
    }
}
