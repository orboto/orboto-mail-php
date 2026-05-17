<?php

declare(strict_types=1);

namespace Orboto\Mail\Dto;

/**
 * Quota envelope returned on every send + queryable via `getQuota()`.
 *
 * Mirrors `QuotaState` in @orboto/mail TypeScript SDK.
 */
final readonly class QuotaState
{
    public function __construct(
        /** Current consumed count within the active period (base + overage). */
        public int $current,
        /** Effective monthly ceiling (base + overage cap). */
        public int $total,
        /** ISO-8601 timestamp when the quota resets. */
        public string $resetAt,
        /** Fraction in [0, ∞) — can exceed 1 within overage allowance. */
        public float $percentUsed,
        /** Threshold at which a `quota-warning` event is fired. */
        public float $softWarnAt,
        /** True once the customer has crossed `softWarnAt` this period. */
        public bool $softWarnTriggered,
        /**
         * When `current >= total`, populated with the specific cap-reason.
         * One of: `base_quota`, `no_payment_method`, `overage_cap`,
         * `daily_cap`, `no_credits`, `monthly_cap_reached`, `hard_gate`.
         */
        public ?string $capReason,
        /**
         * Daily-cap hard limit. `null` for paid tiers, an integer for Free.
         * When set and `dailyRemaining = 0` the next send returns 402
         * `quota_exhausted_daily`.
         */
        public ?int $dailyCap,
        /** Sends consumed today (UTC). `null` when no daily cap is set. */
        public ?int $dailyCurrent,
        /** `dailyCap - dailyCurrent`, never negative. `null` when no cap. */
        public ?int $dailyRemaining,
        /** ISO-8601 timestamp of the next UTC midnight. `null` when no cap. */
        public ?string $dayResetAt,
        /**
         * OMS-29 overage mode: `hard_gate` | `use_credits` | `null` (legacy).
         */
        public ?string $overageMode,
        /** Topup credits remaining (sends). */
        public int $creditBalance,
        /** Monthly overage spend cap in EUR cents. `null` = unlimited. */
        public ?int $monthlyOverageCapEurCents,
        /** Running overage spend for the current month in microcents (1¢ = 10 000). */
        public int $overageUsedThisMonthMicrocents,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            current: (int) $data['current'],
            total: (int) $data['total'],
            resetAt: (string) $data['resetAt'],
            percentUsed: (float) $data['percentUsed'],
            softWarnAt: (float) $data['softWarnAt'],
            softWarnTriggered: (bool) $data['softWarnTriggered'],
            capReason: isset($data['capReason']) ? (string) $data['capReason'] : null,
            dailyCap: isset($data['dailyCap']) ? (int) $data['dailyCap'] : null,
            dailyCurrent: isset($data['dailyCurrent']) ? (int) $data['dailyCurrent'] : null,
            dailyRemaining: isset($data['dailyRemaining']) ? (int) $data['dailyRemaining'] : null,
            dayResetAt: isset($data['dayResetAt']) ? (string) $data['dayResetAt'] : null,
            overageMode: isset($data['overageMode']) ? (string) $data['overageMode'] : null,
            creditBalance: (int) ($data['creditBalance'] ?? 0),
            monthlyOverageCapEurCents: isset($data['monthlyOverageCapEurCents'])
                ? (int) $data['monthlyOverageCapEurCents']
                : null,
            overageUsedThisMonthMicrocents: (int) ($data['overageUsedThisMonthMicrocents'] ?? 0),
        );
    }
}
