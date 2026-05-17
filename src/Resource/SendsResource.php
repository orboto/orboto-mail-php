<?php

declare(strict_types=1);

namespace Orboto\Mail\Resource;

use Orboto\Mail\Dto\SendListItem;
use Orboto\Mail\Http\HttpClient;

final class SendsResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Paginated list, most-recent first.
     *
     * @param array{
     *   limit?: int,
     *   cursor?: string,
     *   status?: string,
     *   region?: string,
     *   since?: string
     * } $options
     *
     * @return array{sends: SendListItem[], nextCursor: ?string}
     */
    public function list(array $options = []): array
    {
        $params = array_filter([
            'limit' => isset($options['limit']) ? (string) $options['limit'] : null,
            'cursor' => $options['cursor'] ?? null,
            'status' => $options['status'] ?? null,
            'region' => $options['region'] ?? null,
            'since' => $options['since'] ?? null,
        ], fn ($v) => $v !== null);
        $query = $params === [] ? '' : '?' . http_build_query($params);
        $response = $this->http->request('GET', '/v1/sends' . $query);
        return [
            'sends' => array_map(
                fn (array $row) => SendListItem::fromArray($row),
                $response['sends'] ?? [],
            ),
            'nextCursor' => $response['nextCursor'] ?? null,
        ];
    }

    public function get(string $id): SendListItem
    {
        $response = $this->http->request('GET', '/v1/sends/' . rawurlencode($id));
        return SendListItem::fromArray($response ?? []);
    }
}
