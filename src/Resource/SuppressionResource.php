<?php

declare(strict_types=1);

namespace Orboto\Mail\Resource;

use Orboto\Mail\Dto\SuppressionCheckResult;
use Orboto\Mail\Dto\SuppressionEntry;
use Orboto\Mail\Http\HttpClient;

final class SuppressionResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /** Check whether an address is on the customer's suppression list. */
    public function check(string $email): SuppressionCheckResult
    {
        $response = $this->http->request('GET', '/v1/suppression/' . rawurlencode($email));
        return SuppressionCheckResult::fromArray($response ?? []);
    }

    /** Manually add an address to the suppression list. */
    public function add(string $email, string $reason = 'manual'): SuppressionEntry
    {
        $response = $this->http->request('POST', '/v1/suppression', [
            'email' => $email,
            'reason' => $reason,
        ]);
        return SuppressionEntry::fromArray($response ?? []);
    }

    /** Remove an address (false-positive recovery). */
    public function remove(string $email): void
    {
        $this->http->request('DELETE', '/v1/suppression/' . rawurlencode($email));
    }

    /**
     * Paginated list, cursor-based.
     *
     * @return array{suppressions: SuppressionEntry[], nextCursor: ?string}
     */
    public function list(?int $limit = null, ?string $cursor = null, ?string $reason = null): array
    {
        $params = array_filter([
            'limit' => $limit !== null ? (string) $limit : null,
            'cursor' => $cursor,
            'reason' => $reason,
        ], fn ($v) => $v !== null);
        $query = $params === [] ? '' : '?' . http_build_query($params);
        $response = $this->http->request('GET', '/v1/suppression' . $query);
        return [
            'suppressions' => array_map(
                fn (array $row) => SuppressionEntry::fromArray($row),
                $response['suppressions'] ?? [],
            ),
            'nextCursor' => $response['nextCursor'] ?? null,
        ];
    }
}
