<?php

declare(strict_types=1);

namespace Orboto\Mail\Resource;

use Orboto\Mail\Dto\Webhook;
use Orboto\Mail\Dto\WebhookWithSecret;
use Orboto\Mail\Http\HttpClient;

final class WebhooksResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * @return Webhook[]
     */
    public function list(): array
    {
        $response = $this->http->request('GET', '/v1/webhooks');
        return array_map(
            fn (array $row) => Webhook::fromArray($row),
            $response['webhooks'] ?? [],
        );
    }

    public function get(string $id): Webhook
    {
        $response = $this->http->request('GET', '/v1/webhooks/' . rawurlencode($id));
        return Webhook::fromArray($response ?? []);
    }

    /**
     * Create a new webhook subscription. The returned `secret` is the
     * plaintext signing key — shown exactly once. Persist it immediately.
     *
     * @param array{
     *   url: string,
     *   label?: string,
     *   eventFilters?: string[]
     * } $input
     */
    public function create(array $input): WebhookWithSecret
    {
        $response = $this->http->request('POST', '/v1/webhooks', $input);
        return WebhookWithSecret::fromArray($response ?? []);
    }

    /**
     * @param array{
     *   url?: string,
     *   label?: ?string,
     *   eventFilters?: string[],
     *   enabled?: bool
     * } $patch
     */
    public function update(string $id, array $patch): Webhook
    {
        $response = $this->http->request('PATCH', '/v1/webhooks/' . rawurlencode($id), $patch);
        return Webhook::fromArray($response ?? []);
    }

    public function remove(string $id): void
    {
        $this->http->request('DELETE', '/v1/webhooks/' . rawurlencode($id));
    }

    /**
     * Re-roll the signing secret. Previous secret is invalidated
     * server-side; new value returned exactly once.
     */
    public function rotateSecret(string $id): WebhookWithSecret
    {
        $response = $this->http->request('POST', '/v1/webhooks/' . rawurlencode($id) . '/rotate-secret', []);
        return WebhookWithSecret::fromArray($response ?? []);
    }
}
