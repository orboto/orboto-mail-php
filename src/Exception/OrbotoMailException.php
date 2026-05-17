<?php

declare(strict_types=1);

namespace Orboto\Mail\Exception;

use Orboto\Mail\Dto\QuotaState;
use RuntimeException;

/**
 * Every non-2xx response from the Orboto Mail Service API becomes an
 * `OrbotoMailException` so consumers have one exception class to catch
 * and branch on (via `$e->getStatusCode()` + `$e->getReason()`).
 *
 * Mirrors `OrbotoMailError` in the @orboto/mail TypeScript SDK.
 */
class OrbotoMailException extends RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        ?string $message = null,
        private readonly ?string $reason = null,
        private readonly ?QuotaState $remainingQuota = null,
        private readonly ?int $retryAfterMs = null,
        private readonly ?array $rawBody = null,
    ) {
        parent::__construct(
            $message ?? "OMS request failed with status {$statusCode}",
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getRemainingQuota(): ?QuotaState
    {
        return $this->remainingQuota;
    }

    public function getRetryAfterMs(): ?int
    {
        return $this->retryAfterMs;
    }

    public function getRawBody(): ?array
    {
        return $this->rawBody;
    }

    /**
     * True when the request is worth retrying with backoff (transient).
     * The HttpClient calls this internally; user code rarely needs it.
     */
    public function isRetryable(): bool
    {
        return in_array($this->statusCode, [502, 503, 504], true);
    }
}
