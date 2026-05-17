<?php

declare(strict_types=1);

namespace Orboto\Mail;

use InvalidArgumentException;
use Orboto\Mail\Dto\ConnectionRevokedEvent;
use Orboto\Mail\Dto\QuotaState;
use Orboto\Mail\Dto\SendBatchResult;
use Orboto\Mail\Dto\SendResult;
use Orboto\Mail\Http\HttpClient;
use Orboto\Mail\Http\QuotaEmitter;
use Orboto\Mail\Resource\ApiKeysResource;
use Orboto\Mail\Resource\InboundResource;
use Orboto\Mail\Resource\SendsResource;
use Orboto\Mail\Resource\SenderDomainsResource;
use Orboto\Mail\Resource\SuppressionResource;
use Orboto\Mail\Resource\TemplatesResource;
use Orboto\Mail\Resource\WebhooksResource;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Official PHP SDK for the Orboto Mail Service.
 *
 * Quick start:
 *
 *   use Orboto\Mail\OrbotoMail;
 *
 *   $mail = new OrbotoMail(['apiKey' => $_ENV['OMS_API_KEY']]);
 *
 *   $result = $mail->send([
 *     'from'    => 'noreply@acme.orbo.to',
 *     'to'      => 'user@example.com',
 *     'subject' => 'Welcome',
 *     'html'    => '<h1>Welcome!</h1>',
 *   ]);
 *
 *   // $result->messageId      — SES message-id
 *   // $result->status         — 'queued' at success-time
 *   // $result->remainingQuota — quota state AFTER this send
 *
 *   $mail->on('quota-warning',   fn (QuotaState $q) => error_log("80%"));
 *   $mail->on('quota-low',       fn (QuotaState $q) => error_log("95%"));
 *   $mail->on('quota-exhausted', fn (QuotaState $q) => error_log("done"));
 *   $mail->on('connection-revoked', fn (ConnectionRevokedEvent $e) => error_log($e->message));
 *
 * Mirrors the @orboto/mail TypeScript SDK feature-for-feature. See
 * https://mail.orboto.io for full docs.
 */
final class OrbotoMail
{
    /** Default API base URL. nginx-edge at mail.orboto.io reverse-proxies /api/v1/* → Fastify :3000. */
    private const DEFAULT_BASE_URL = 'https://mail.orboto.io/api';

    private readonly HttpClient $http;
    private readonly QuotaEmitter $quotaEmitter;
    /** @var array<string, array<int, callable>> */
    private array $listeners = [];

    public readonly SuppressionResource $suppression;
    public readonly TemplatesResource $templates;
    public readonly WebhooksResource $webhooks;
    public readonly SendsResource $sends;
    public readonly InboundResource $inbound;
    public readonly SenderDomainsResource $senderDomains;
    public readonly ApiKeysResource $apiKeys;

    /**
     * @param array{
     *   apiKey?: string,
     *   baseUrl?: string,
     *   timeout?: float,
     *   maxRetries?: int,
     *   httpClient?: ClientInterface,
     *   requestFactory?: RequestFactoryInterface,
     *   streamFactory?: StreamFactoryInterface
     * } $options
     */
    public function __construct(array $options = [])
    {
        // Resolve API key: explicit option > OMS_API_KEY env var.
        $apiKey = $options['apiKey'] ?? getenv('OMS_API_KEY') ?: ($_ENV['OMS_API_KEY'] ?? null);
        if (!is_string($apiKey) || $apiKey === '') {
            throw new InvalidArgumentException(
                'orboto/mail: no API key provided. Pass "apiKey" to the constructor or set OMS_API_KEY in your environment.',
            );
        }

        $baseUrl = $options['baseUrl'] ?? getenv('OMS_BASE_URL') ?: self::DEFAULT_BASE_URL;

        $this->quotaEmitter = new QuotaEmitter();

        $this->http = new HttpClient(
            apiKey: $apiKey,
            baseUrl: is_string($baseUrl) ? $baseUrl : self::DEFAULT_BASE_URL,
            timeoutSeconds: (float) ($options['timeout'] ?? 10.0),
            maxRetries: (int) ($options['maxRetries'] ?? 3),
            client: $options['httpClient'] ?? null,
            requestFactory: $options['requestFactory'] ?? null,
            streamFactory: $options['streamFactory'] ?? null,
            onQuota: function (QuotaState $q): void {
                foreach ($this->quotaEmitter->inspect($q) as $event) {
                    $this->emit($event, $q);
                }
            },
            onConnectionRevoked: function (string $message): void {
                $this->emit('connection-revoked', new ConnectionRevokedEvent(
                    reason: 'connection_revoked',
                    message: $message,
                ));
            },
        );

        $this->suppression = new SuppressionResource($this->http);
        $this->templates = new TemplatesResource($this->http);
        $this->webhooks = new WebhooksResource($this->http);
        $this->sends = new SendsResource($this->http);
        $this->inbound = new InboundResource($this->http);
        $this->senderDomains = new SenderDomainsResource($this->http);
        $this->apiKeys = new ApiKeysResource($this->http);
    }

    /**
     * Send a transactional email.
     *
     * @param array{
     *   from: string,
     *   to: string,
     *   subject: string,
     *   html?: string,
     *   text?: string,
     *   tags?: array<string,string>
     * } $input
     *
     * @throws \Orboto\Mail\Exception\OrbotoMailException
     */
    public function send(array $input): SendResult
    {
        if (!isset($input['html']) && !isset($input['text'])) {
            throw new InvalidArgumentException(
                'orboto/mail: send() requires at least one of "html" or "text".',
            );
        }
        $response = $this->http->request('POST', '/v1/send', $input);
        return SendResult::fromArray($response ?? []);
    }

    /**
     * Send up to 100 messages in one HTTP call. Per-item processing —
     * partial failures are surfaced in `$result->results`; the call as a
     * whole always returns 200. Inspect `$result->summary` + per-item
     * `ok` flag to decide whether to retry indices.
     *
     * @param array{messages: array<int, array<string,mixed>>} $input
     */
    public function sendBatch(array $input): SendBatchResult
    {
        if (!isset($input['messages']) || !is_array($input['messages']) || count($input['messages']) === 0) {
            throw new InvalidArgumentException(
                'orboto/mail: sendBatch() requires a non-empty "messages" array.',
            );
        }
        if (count($input['messages']) > 100) {
            throw new InvalidArgumentException(
                'orboto/mail: sendBatch() is capped at 100 messages per call.',
            );
        }
        $response = $this->http->request('POST', '/v1/send/batch', $input);
        return SendBatchResult::fromArray($response ?? []);
    }

    /**
     * Render a template + send. Equivalent to send() with template-id
     * resolved server-side. Server validates `variables` against the
     * template's variables-schema; missing / wrong-typed vars return
     * 400 `template_variable_validation`.
     *
     * @param array{
     *   templateId: string,
     *   to: string,
     *   variables: array<string,mixed>,
     *   from?: string,
     *   tags?: array<string,string>
     * } $input
     */
    public function sendTemplate(array $input): SendResult
    {
        $response = $this->http->request('POST', '/v1/send/template', $input);
        return SendResult::fromArray($response ?? []);
    }

    /** Standalone quota-check. Useful for "should I send a batch?" planning. */
    public function getQuota(): QuotaState
    {
        $response = $this->http->request('GET', '/v1/quota');
        return QuotaState::fromArray($response['quota'] ?? []);
    }

    /**
     * Subscribe to a lifecycle event. Supported events:
     *
     *   - `quota-warning`         payload: QuotaState (fired once at 80 % per reset)
     *   - `quota-low`             payload: QuotaState (fired once at 95 % per reset)
     *   - `quota-exhausted`       payload: QuotaState (fired once at 100 % per reset)
     *   - `connection-revoked`    payload: ConnectionRevokedEvent
     */
    public function on(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    private function emit(string $event, mixed $payload): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener($payload);
        }
    }
}
