<?php

declare(strict_types=1);

namespace Orboto\Mail\Exception;

/**
 * Thrown when the API returns 402 with reason `payment_required`
 * (OMS-98): the monthly included quota is used up AND the account
 * wallet balance is too low to cover an above-quota (overage) send.
 *
 * Distinct from {@see QuotaExhaustedException} so a Laravel app can
 * catch it specifically to render a wallet-top-up / billing prompt.
 * Inspect `$e->getRemainingQuota()` for the subscription snapshot.
 * Not retryable - the balance won't change without a top-up.
 */
class PaymentRequiredException extends OrbotoMailException
{
}
