<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class ApiKey
{
    public function __construct(
        public string $id,
        public ?string $name,
        /** Display-prefix like `oms_live_a1b2c3d4`. */
        public string $prefix,
        public string $createdAt,
        public ?string $lastUsedAt,
        public ?string $revokedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            name: isset($data['name']) ? (string) $data['name'] : null,
            prefix: (string) $data['prefix'],
            createdAt: (string) $data['createdAt'],
            lastUsedAt: isset($data['lastUsedAt']) ? (string) $data['lastUsedAt'] : null,
            revokedAt: isset($data['revokedAt']) ? (string) $data['revokedAt'] : null,
        );
    }
}
