<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class Template
{
    public function __construct(
        public string $id,
        public string $name,
        public string $subject,
        public ?string $bodyHtml,
        public ?string $bodyText,
        /** Zod/JSON-Schema-compatible description of the `variables` shape. */
        public ?array $variablesSchema,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            name: (string) $data['name'],
            subject: (string) $data['subject'],
            bodyHtml: isset($data['bodyHtml']) ? (string) $data['bodyHtml'] : null,
            bodyText: isset($data['bodyText']) ? (string) $data['bodyText'] : null,
            variablesSchema: $data['variablesSchema'] ?? null,
            createdAt: (string) $data['createdAt'],
            updatedAt: (string) $data['updatedAt'],
        );
    }
}
