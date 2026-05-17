<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

/**
 * Returned by `apiKeys()->create()` and `apiKeys()->rotate()` — the
 * plaintext key is included exactly once. Persist it immediately;
 * subsequent reads strip the field.
 */
final readonly class ApiKeyWithSecret
{
    public function __construct(
        public string $id,
        public ?string $name,
        public string $prefix,
        public string $createdAt,
        public ?string $lastUsedAt,
        public ?string $revokedAt,
        /** Plaintext secret. Capture immediately + never reachable again. */
        public string $key,
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
            key: (string) $data['key'],
        );
    }
}
