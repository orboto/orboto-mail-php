<?php

declare(strict_types=1);

namespace Orboto\Mail\Resource;

use Orboto\Mail\Dto\Template;
use Orboto\Mail\Http\HttpClient;

final class TemplatesResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * @return Template[]
     */
    public function list(): array
    {
        $response = $this->http->request('GET', '/v1/templates');
        return array_map(
            fn (array $row) => Template::fromArray($row),
            $response['templates'] ?? [],
        );
    }

    public function get(string $id): Template
    {
        $response = $this->http->request('GET', '/v1/templates/' . rawurlencode($id));
        return Template::fromArray($response ?? []);
    }

    /**
     * @param array{
     *   name: string,
     *   subject: string,
     *   bodyHtml?: string,
     *   bodyText?: string,
     *   variablesSchema?: array<string,mixed>
     * } $input
     */
    public function create(array $input): Template
    {
        $response = $this->http->request('POST', '/v1/templates', $input);
        return Template::fromArray($response ?? []);
    }

    /**
     * @param array{
     *   name?: string,
     *   subject?: string,
     *   bodyHtml?: ?string,
     *   bodyText?: ?string,
     *   variablesSchema?: ?array<string,mixed>
     * } $patch
     */
    public function update(string $id, array $patch): Template
    {
        $response = $this->http->request('PATCH', '/v1/templates/' . rawurlencode($id), $patch);
        return Template::fromArray($response ?? []);
    }

    public function remove(string $id): void
    {
        $this->http->request('DELETE', '/v1/templates/' . rawurlencode($id));
    }
}
