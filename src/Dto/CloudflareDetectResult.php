<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class CloudflareDetectResult
{
    public function __construct(
        public bool $onCloudflare,
        /** @var string[] */
        public array $nameservers,
        public string $resolvedFor,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            onCloudflare: (bool) $data['onCloudflare'],
            nameservers: array_map('strval', $data['nameservers'] ?? []),
            resolvedFor: (string) $data['resolvedFor'],
        );
    }
}
