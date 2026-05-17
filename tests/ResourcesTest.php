<?php

declare(strict_types=1);

namespace Orboto\Mail\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use Orboto\Mail\OrbotoMail;
use Orboto\Mail\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\TestCase;

final class ResourcesTest extends TestCase
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

    public function test_suppression_check_returns_dto(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(200, [
            'email' => 'a@b.com',
            'suppressed' => true,
            'entry' => [
                'email' => 'a@b.com',
                'reason' => 'hard-bounce',
                'addedAt' => '2026-05-17T00:00:00.000Z',
                'addedBy' => 'system',
            ],
        ]);
        $result = $this->mail($fake)->suppression->check('a@b.com');
        $this->assertTrue($result->suppressed);
        $this->assertNotNull($result->entry);
        $this->assertSame('hard-bounce', $result->entry->reason);
    }

    public function test_suppression_add_posts_with_reason(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(200, [
            'email' => 'a@b.com',
            'reason' => 'manual',
            'addedAt' => '2026-05-17T00:00:00.000Z',
            'addedBy' => 'api-key',
        ]);
        $entry = $this->mail($fake)->suppression->add('a@b.com');
        $this->assertSame('manual', $entry->reason);
        // POST + body present.
        $this->assertSame('POST', $fake->requests[0]->getMethod());
        $this->assertStringContainsString('a@b.com', (string) $fake->requests[0]->getBody());
    }

    public function test_templates_list_returns_dtos(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(200, [
            'templates' => [
                [
                    'id' => '11111111-1111-1111-1111-111111111111',
                    'name' => 'welcome',
                    'subject' => 'Welcome',
                    'bodyHtml' => '<p>hi</p>',
                    'bodyText' => 'hi',
                    'variablesSchema' => null,
                    'createdAt' => '2026-05-17T00:00:00.000Z',
                    'updatedAt' => '2026-05-17T00:00:00.000Z',
                ],
            ],
        ]);
        $templates = $this->mail($fake)->templates->list();
        $this->assertCount(1, $templates);
        $this->assertSame('welcome', $templates[0]->name);
    }

    public function test_api_keys_create_returns_secret(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(200, [
            'id' => '22222222-2222-2222-2222-222222222222',
            'name' => 'prod-key',
            'prefix' => 'oms_live_abcd1234',
            'key' => 'oms_live_abcd1234fullsecret',
            'createdAt' => '2026-05-17T00:00:00.000Z',
            'lastUsedAt' => null,
            'revokedAt' => null,
        ]);
        $key = $this->mail($fake)->apiKeys->create(['name' => 'prod-key']);
        $this->assertSame('oms_live_abcd1234fullsecret', $key->key);
        $this->assertSame('prod-key', $key->name);
    }

    public function test_webhooks_create_returns_secret(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(200, [
            'id' => '33333333-3333-3333-3333-333333333333',
            'url' => 'https://hook.example.com/in',
            'label' => null,
            'eventFilters' => [],
            'enabled' => true,
            'lastSuccessAt' => null,
            'lastFailureAt' => null,
            'lastFailureReason' => null,
            'createdAt' => '2026-05-17T00:00:00.000Z',
            'secret' => 'a' . str_repeat('0', 63),
        ]);
        $webhook = $this->mail($fake)->webhooks->create(['url' => 'https://hook.example.com/in']);
        $this->assertNotEmpty($webhook->secret);
        $this->assertSame(64, strlen($webhook->secret));
    }

    public function test_sends_list_passes_filters_as_query_params(): void
    {
        $fake = new FakeHttpClient();
        $fake->queue(200, ['sends' => [], 'nextCursor' => null]);
        $this->mail($fake)->sends->list(['limit' => 20, 'status' => 'delivered']);
        $uri = (string) $fake->requests[0]->getUri();
        $this->assertStringContainsString('limit=20', $uri);
        $this->assertStringContainsString('status=delivered', $uri);
    }
}
