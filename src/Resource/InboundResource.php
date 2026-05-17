<?php

declare(strict_types=1);

namespace Orboto\Mail\Resource;

use Orboto\Mail\Dto\InboundDetail;
use Orboto\Mail\Dto\InboundMail;
use Orboto\Mail\Http\HttpClient;

final class InboundResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Paginated list, most-recent first. Body is NOT in the list response —
     * call `get()` for the presigned download-URL.
     *
     * @param array{limit?: int, cursor?: string} $options
     *
     * @return array{inbound: InboundMail[], nextCursor: ?string}
     */
    public function list(array $options = []): array
    {
        $params = array_filter([
            'limit' => isset($options['limit']) ? (string) $options['limit'] : null,
            'cursor' => $options['cursor'] ?? null,
        ], fn ($v) => $v !== null);
        $query = $params === [] ? '' : '?' . http_build_query($params);
        $response = $this->http->request('GET', '/v1/inbound' . $query);
        return [
            'inbound' => array_map(
                fn (array $row) => InboundMail::fromArray($row),
                $response['inbound'] ?? [],
            ),
            'nextCursor' => $response['nextCursor'] ?? null,
        ];
    }

    public function get(string $id): InboundDetail
    {
        $response = $this->http->request('GET', '/v1/inbound/' . rawurlencode($id));
        return InboundDetail::fromArray($response ?? []);
    }
}
