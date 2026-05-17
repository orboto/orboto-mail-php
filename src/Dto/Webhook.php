<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class Webhook
{
    public function __construct(
        public string $id,
        public string $url,
        public ?string $label,
        /** @var string[] */
        public array $eventFilters,
        public bool $enabled,
        public ?string $lastSuccessAt,
        public ?string $lastFailureAt,
        public ?string $lastFailureReason,
        public string $createdAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            url: (string) $data['url'],
            label: isset($data['label']) ? (string) $data['label'] : null,
            eventFilters: array_map('strval', $data['eventFilters'] ?? []),
            enabled: (bool) $data['enabled'],
            lastSuccessAt: isset($data['lastSuccessAt']) ? (string) $data['lastSuccessAt'] : null,
            lastFailureAt: isset($data['lastFailureAt']) ? (string) $data['lastFailureAt'] : null,
            lastFailureReason: isset($data['lastFailureReason']) ? (string) $data['lastFailureReason'] : null,
            createdAt: (string) $data['createdAt'],
        );
    }
}
