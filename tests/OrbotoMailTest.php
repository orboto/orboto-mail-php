<?php

declare(strict_types=1);

namespace Orboto\Mail\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use Orboto\Mail\Dto\ConnectionRevokedEvent;
use Orboto\Mail\Dto\QuotaState;
use Orboto\Mail\Exception\ConnectionRevokedException;
use Orboto\Mail\Exception\OrbotoMailException;
use Orboto\Mail\Exception\QuotaExhaustedException;
use Orboto\Mail\Exception\SuppressedRecipientException;
use Orboto\Mail\OrbotoMail;
use Orboto\Mail\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\TestCase;

final class OrbotoMailTest extends TestCase
{
    private function mail(FakeHttpClient $fake): OrbotoMail
    {
        $factory = new HttpFactory();
        return new OrbotoMail([
            'apiKey' => 'oms_live_test',
            'baseUrl' => 'https://mail.test/api',
            'httpClient' => $fake,
            'requestFactory' => $factory,
            'streamFactory' => $factory,
            'maxRetries' => 0,
        ]);
    }

    private static function quotaPayload(float $percentUsed = 0.5): array
    {
        return [
            'current' => (int) ($percentUsed * 1000),
            'total' => 1000,
            'resetAt' => '2026-06-01T00:00:00.000Z',
            'percentUsed' => $percentUsed,
            'softWarnAt' => 0.8,
            'softWarnTriggered' => false,
            'dailyCap' => null,
            'dailyCurrent' => null,
            'dailyRemaining' => null,
            'dayResetAt' => null,
            'overageMode' => null,
            'creditBalance' => 0,
            'monthlyOverageCapEurCents' => null,
            'overageUsedThisMonthMicrocents' => 0,
        ];
    }

    public function test_constructor_requires_api_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrbotoMail([]);
    }

    public function test_send_happy_path_returns_send_result(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(200, [
            'messageId' => 'msg-1',
            'status' => 'queued',
            'overage' => false,
            'remainingQuota' => self::quotaPayload(0.5),
        ]);
        $result = $this->mail($fake)->send([
            'from' => 'noreply@acme.orbo.to',
            'to' => 'user@example.com',
            'subject' => 'hi',
            'html' => '<p>hi</p>',
        ]);
        $this->assertSame('msg-1', $result->messageId);
        $this->assertSame('queued', $result->status);
        $this->assertSame(1000, $result->remainingQuota->total);
    }

    public function test_send_requires_html_or_text(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mail(new FakeHttpClient())->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 'no body',
        ]);
    }

    public function test_402_becomes_quota_exhausted_exception_with_snapshot(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(402, [
            'error' => 'quota_exhausted',
            'reason' => 'base_quota',
            'message' => 'Monthly quota exhausted',
            'remainingQuota' => self::quotaPayload(1.0),
        ]);

        try {
            $this->mail($fake)->send([
                'from' => 'a@b.com',
                'to' => 'c@d.com',
                'subject' => 'over',
                'text' => 'over',
            ]);
            $this->fail('expected QuotaExhaustedException');
        } catch (QuotaExhaustedException $e) {
            $this->assertSame(402, $e->getStatusCode());
            $this->assertSame('base_quota', $e->getReason());
            $this->assertNotNull($e->getRemainingQuota());
            $this->assertSame(1.0, $e->getRemainingQuota()->percentUsed);
        }
    }

    public function test_400_recipient_suppressed_typed(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(400, [
            'error' => 'send_rejected',
            'reason' => 'recipient_suppressed',
            'message' => 'Recipient is on the suppression list',
        ]);
        $this->expectException(SuppressedRecipientException::class);
        $this->mail($fake)->send([
            'from' => 'a@b.com',
            'to' => 'bounce@example.com',
            'subject' => 'suppressed',
            'text' => 'x',
        ]);
    }

    public function test_401_connection_revoked_fires_event_and_throws_typed(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(401, [
            'error' => 'unauthorized',
            'reason' => 'connection_revoked',
            'message' => 'Customer revoked this connection',
        ]);
        $mail = $this->mail($fake);
        /** @var ConnectionRevokedEvent|null $captured */
        $captured = null;
        $mail->on('connection-revoked', function (ConnectionRevokedEvent $e) use (&$captured): void {
            $captured = $e;
        });
        try {
            $mail->send([
                'from' => 'a@b.com',
                'to' => 'c@d.com',
                'subject' => 'rev',
                'text' => 'x',
            ]);
            $this->fail('expected ConnectionRevokedException');
        } catch (ConnectionRevokedException $e) {
            $this->assertSame(401, $e->getStatusCode());
            $this->assertNotNull($captured);
            $this->assertSame('connection_revoked', $captured->reason);
        }
    }

    public function test_quota_events_fire_at_thresholds(): void
    {
        $fake = new FakeHttpClient();
        // Three sends crossing 0.5 → 0.85 → 0.97 → 1.0.
        $fake->queue(200, ['messageId' => '1', 'status' => 'queued', 'overage' => false, 'remainingQuota' => self::quotaPayload(0.5)]);
        $fake->queue(200, ['messageId' => '2', 'status' => 'queued', 'overage' => false, 'remainingQuota' => self::quotaPayload(0.85)]);
        $fake->queue(200, ['messageId' => '3', 'status' => 'queued', 'overage' => false, 'remainingQuota' => self::quotaPayload(0.97)]);
        $fake->queue(200, ['messageId' => '4', 'status' => 'queued', 'overage' => false, 'remainingQuota' => self::quotaPayload(1.0)]);

        $mail = $this->mail($fake);
        $events = [];
        $mail->on('quota-warning', function (QuotaState $q) use (&$events): void { $events[] = 'warning@' . $q->percentUsed; });
        $mail->on('quota-low', function (QuotaState $q) use (&$events): void { $events[] = 'low@' . $q->percentUsed; });
        $mail->on('quota-exhausted', function (QuotaState $q) use (&$events): void { $events[] = 'exhausted@' . $q->percentUsed; });

        for ($i = 0; $i < 4; $i++) {
            $mail->send(['from' => 'a@b.com', 'to' => 'c@d.com', 'subject' => 's', 'text' => 't']);
        }

        $this->assertSame(['warning@0.85', 'low@0.97', 'exhausted@1'], $events);
    }

    public function test_quota_events_fire_once_per_reset_period(): void
    {
        $fake = new FakeHttpClient();
        // Two sends both at 0.9 — same resetAt → only one warning.
        $fake->queue(200, ['messageId' => '1', 'status' => 'queued', 'overage' => false, 'remainingQuota' => self::quotaPayload(0.9)]);
        $fake->queue(200, ['messageId' => '2', 'status' => 'queued', 'overage' => false, 'remainingQuota' => self::quotaPayload(0.9)]);

        $mail = $this->mail($fake);
        $count = 0;
        $mail->on('quota-warning', function () use (&$count): void { $count++; });
        $mail->send(['from' => 'a@b.com', 'to' => 'c@d.com', 'subject' => 's', 'text' => 't']);
        $mail->send(['from' => 'a@b.com', 'to' => 'c@d.com', 'subject' => 's', 'text' => 't']);
        $this->assertSame(1, $count);
    }

    public function test_get_quota_returns_dto(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(200, ['quota' => self::quotaPayload(0.42)]);
        $q = $this->mail($fake)->getQuota();
        $this->assertSame(0.42, $q->percentUsed);
        $this->assertSame(1000, $q->total);
    }

    public function test_send_batch_cap_enforced(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mail(new FakeHttpClient())->sendBatch([
            'messages' => array_fill(0, 101, ['from' => 'a@b.com', 'to' => 'c@d.com', 'subject' => 's', 'text' => 't']),
        ]);
    }

    public function test_send_batch_empty_array_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mail(new FakeHttpClient())->sendBatch(['messages' => []]);
    }

    public function test_503_is_retryable_marker_on_exception(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(503, ['error' => 'unavailable', 'message' => 'temp unavail']);
        try {
            $this->mail($fake)->send(['from' => 'a@b.com', 'to' => 'c@d.com', 'subject' => 's', 'text' => 't']);
            $this->fail('expected OrbotoMailException');
        } catch (OrbotoMailException $e) {
            $this->assertSame(503, $e->getStatusCode());
            $this->assertTrue($e->isRetryable());
        }
    }
}
