<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

final readonly class ConnectionRevokedEvent
{
    public function __construct(
        public string $reason,
        public string $message,
    ) {
    }
}
