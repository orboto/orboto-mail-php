<?php

declare(strict_types=1);

namespace Orboto\Mail\Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Simple in-memory PSR-18 client for tests. Returns a queued response
 * per call and records every request.
 */
final class FakeHttpClient implements ClientInterface
{
    /** @var array<int, array{status:int,body:string}> */
    public array $queuedResponses = [];

    /** @var array<int, RequestInterface> */
    public array $requests = [];

    public function queue(int $status, string|array $body): void
    {
        $this->queuedResponses[] = [
            'status' => $status,
            'body' => is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body,
        ];
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        if ($this->queuedResponses === []) {
            return new Response(500, [], json_encode(['error' => 'no_queued_response', 'message' => 'FakeHttpClient out of responses']));
        }
        $next = array_shift($this->queuedResponses);
        return new Response($next['status'], ['Content-Type' => 'application/json'], $next['body']);
    }
}
