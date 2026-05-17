<?php

declare(strict_types=1);

namespace Orboto\Mail\Http;

use Orboto\Mail\Dto\QuotaState;

/**
 * Quota event dispatcher. Tracks the per-event "have we already fired
 * this threshold?" state so a customer who lingers at 81 % doesn't get
 * a `quota-warning` for every send.
 *
 * Thresholds + emit-once-per-reset semantics mirror QuotaEmitter in the
 * @orboto/mail TypeScript SDK:
 *
 *   - 80 %  → 'quota-warning'  (fires once until quota.resetAt rolls)
 *   - 95 %  → 'quota-low'      (same)
 *   - 100 % → 'quota-exhausted' (same)
 */
final class QuotaEmitter
{
    private const THRESHOLDS = [
        ['name' => 'warning',   'pct' => 0.80, 'event' => 'quota-warning'],
        ['name' => 'low',       'pct' => 0.95, 'event' => 'quota-low'],
        ['name' => 'exhausted', 'pct' => 1.00, 'event' => 'quota-exhausted'],
    ];

    /** @var array<string, array<string, bool>> */
    private array $firedSinceReset = [];

    private ?string $lastResetAt = null;

    /**
     * Inspect a quota snapshot and return the list of event names to emit.
     *
     * @return string[]
     */
    public function inspect(QuotaState $quota): array
    {
        if ($this->lastResetAt !== null && $this->lastResetAt !== $quota->resetAt) {
            $this->firedSinceReset = [];
        }
        $this->lastResetAt = $quota->resetAt;

        $bucket = $this->firedSinceReset[$quota->resetAt] ?? [];
        $toEmit = [];
        foreach (self::THRESHOLDS as $threshold) {
            if ($quota->percentUsed >= $threshold['pct'] && empty($bucket[$threshold['name']])) {
                $bucket[$threshold['name']] = true;
                $toEmit[] = $threshold['event'];
            }
        }
        $this->firedSinceReset[$quota->resetAt] = $bucket;

        return $toEmit;
    }
}
