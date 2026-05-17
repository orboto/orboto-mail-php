<?php

declare(strict_types=1);

namespace Orboto\Mail\Resource;

use Orboto\Mail\Dto\ApiKey;
use Orboto\Mail\Dto\ApiKeyWithSecret;
use Orboto\Mail\Http\HttpClient;

final class ApiKeysResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * List all API keys on this account (no plaintext).
     *
     * @return ApiKey[]
     */
    public function list(): array
    {
        $response = $this->http->request('GET', '/v1/api-keys');
        return array_map(
            fn (array $row) => ApiKey::fromArray($row),
            $response['apiKeys'] ?? [],
        );
    }

    /** Get one API key by id (no plaintext). */
    public function get(string $id): ApiKey
    {
        $response = $this->http->request('GET', '/v1/api-keys/' . rawurlencode($id));
        return ApiKey::fromArray($response ?? []);
    }

    /**
     * Mint a new API key. Plaintext `key` returned exactly ONCE —
     * capture immediately, subsequent reads strip the field.
     *
     * @param array{name?: string, mode?: 'live'|'test'} $input
     */
    public function create(array $input = []): ApiKeyWithSecret
    {
        $response = $this->http->request('POST', '/v1/api-keys', $input);
        return ApiKeyWithSecret::fromArray($response ?? []);
    }

    /** Immediately revoke an API key. */
    public function revoke(string $id): void
    {
        $this->http->request('DELETE', '/v1/api-keys/' . rawurlencode($id));
    }

    /**
     * Atomic rotate — mints a fresh key with the same name + revokes
     * the old one in a single TXN. New plaintext returned ONCE; capture
     * before the call returns.
     */
    public function rotate(string $id): ApiKeyWithSecret
    {
        $response = $this->http->request('POST', '/v1/api-keys/' . rawurlencode($id) . '/rotate', []);
        return ApiKeyWithSecret::fromArray($response ?? []);
    }
}
