<?php

declare(strict_types=1);

namespace Orboto\Mail\Exception;

/**
 * Thrown when the API returns 503 with reason `wallet_unavailable`
 * (OMS-98): the overage-billing wallet was unreachable, so an
 * above-quota send was NOT dispatched (fail-closed).
 *
 * Transient - `isRetryable()` is true (503), and the SDK auto-retries
 * it like any other 503 before this surfaces. Treat a surfaced
 * instance as "try again shortly", not as a hard billing failure.
 */
class WalletUnavailableException extends OrbotoMailException
{
}
