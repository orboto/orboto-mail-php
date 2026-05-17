<?php

declare(strict_types=1);

namespace Orboto\Mail\Exception;

/**
 * Thrown when the API returns 402 with a `quota_exhausted_*` reason.
 * Inspect `$e->getRemainingQuota()` for the snapshot to render an
 * actionable upgrade prompt.
 *
 * Caller can branch on `getReason()`:
 *   - `base_quota`                  — base monthly cap hit, no overage opt-in
 *   - `quota_exhausted_no_overage_opted_in` — same, alternate API spelling
 *   - `quota_exhausted_daily`       — Free tier daily cap (resets UTC midnight)
 *   - `overage_cap`                 — over-base + paid, but tier cap reached
 *   - `no_payment_method`           — overage opted in, but no card on file
 *   - `no_credits`                  — credit-mode customer, balance = 0
 *   - `hard_gate`                   — quota mode = hard_gate, refuse over-base
 *   - `monthly_cap_reached`         — credit-mode monthly euro-cap hit
 */
class QuotaExhaustedException extends OrbotoMailException
{
}
