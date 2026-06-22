<?php

declare(strict_types=1);

namespace Orboto\Mail\Http;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Orboto\Mail\Dto\QuotaState;
use Orboto\Mail\Exception\ConnectionRevokedException;
use Orboto\Mail\Exception\OrbotoMailException;
use Orboto\Mail\Exception\PaymentRequiredException;
use Orboto\Mail\Exception\QuotaExhaustedException;
use Orboto\Mail\Exception\SuppressedRecipientException;
use Orboto\Mail\Exception\WalletUnavailableException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

/**
 * Internal HTTP client. Handles:
 *
 *  - Bearer-token authorization
 *  - JSON marshalling
 *  - Retry-with-exponential-backoff on transient errors (HTTP 502 / 503 /
 *    504 + network errors)
 *  - Connection-revoked detection (401 reason='connection_revoked'
 *    surfaces as ConnectionRevokedException + onConnectionRevoked
 *    callback)
 *  - Quota-event detection (every 2xx response with a `remainingQuota`
 *    field invokes the onQuota callback)
 *  - Suppressed-recipient typing (400 reason='recipient_suppressed'
 *    becomes SuppressedRecipientException so callers can branch cleanly)
 *  - 402 → QuotaExhaustedException
 *
 * Not for direct use — consumers instantiate `Orboto\Mail\OrbotoMail`.
 */
final class HttpClient
{
    private readonly ClientInterface $client;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly StreamFactoryInterface $streamFactory;
    private readonly string $apiKey;
    private readonly string $baseUrl;
    private readonly float $timeoutSeconds;
    private readonly int $maxRetries;
    /** @var (callable(QuotaState): void)|null */
    private $onQuota;
    /** @var (callable(string): void)|null */
    private $onConnectionRevoked;

    public function __construct(
        string $apiKey,
        string $baseUrl,
        float $timeoutSeconds = 10.0,
        int $maxRetries = 3,
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?callable $onQuota = null,
        ?callable $onConnectionRevoked = null,
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeoutSeconds = $timeoutSeconds;
        $this->maxRetries = $maxRetries;
        $this->onQuota = $onQuota;
        $this->onConnectionRevoked = $onConnectionRevoked;
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * Execute a JSON request and return the decoded response body.
     *
     * @return array<string,mixed>|null Decoded JSON, or null on 2xx-no-body.
     *
     * @throws OrbotoMailException
     */
    public function request(string $method, string $path, ?array $body = null): ?array
    {
        $lastError = null;
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $request = $this->requestFactory
                    ->createRequest($method, $this->baseUrl . $path)
                    ->withHeader('Authorization', 'Bearer ' . $this->apiKey)
                    ->withHeader('Content-Type', 'application/json')
                    ->withHeader('Accept', 'application/json')
                    ->withHeader('User-Agent', 'orboto-mail-php/0.3.1');

                if ($body !== null) {
                    $payload = json_encode($body, JSON_THROW_ON_ERROR);
                    $request = $request->withBody($this->streamFactory->createStream($payload));
                }

                $response = $this->client->sendRequest($request);
                $status = $response->getStatusCode();
                $rawBody = (string) $response->getBody();
                $parsed = $rawBody === '' ? null : json_decode($rawBody, true);

                if ($status >= 200 && $status < 300) {
                    if (
                        is_array($parsed)
                        && isset($parsed['remainingQuota'])
                        && is_array($parsed['remainingQuota'])
                        && $this->onQuota !== null
                    ) {
                        ($this->onQuota)(QuotaState::fromArray($parsed['remainingQuota']));
                    }
                    return is_array($parsed) ? $parsed : null;
                }

                $errBody = is_array($parsed) ? $parsed : null;
                $message = $errBody['message'] ?? "OMS request failed with status {$status}";
                $reason = $errBody['reason'] ?? null;
                $remainingQuota = isset($errBody['remainingQuota']) && is_array($errBody['remainingQuota'])
                    ? QuotaState::fromArray($errBody['remainingQuota'])
                    : null;
                $retryAfterMs = isset($errBody['retryAfterMs']) ? (int) $errBody['retryAfterMs'] : null;

                if ($status === 401 && $reason === 'connection_revoked') {
                    if ($this->onConnectionRevoked !== null) {
                        ($this->onConnectionRevoked)($message);
                    }
                    throw new ConnectionRevokedException($status, $message, $reason, $remainingQuota, $retryAfterMs, $errBody);
                }

                // OMS-98 - wallet-gated overage: 402 payment_required is
                // distinct from a plain quota_exhausted (the subscription
                // quota is gone AND the wallet is empty).
                if ($status === 402 && $reason === 'payment_required') {
                    throw new PaymentRequiredException($status, $message, $reason, $remainingQuota, $retryAfterMs, $errBody);
                }

                if ($status === 402) {
                    throw new QuotaExhaustedException($status, $message, $reason, $remainingQuota, $retryAfterMs, $errBody);
                }

                if ($status === 400 && $reason === 'recipient_suppressed') {
                    throw new SuppressedRecipientException($status, $message, $reason, $remainingQuota, $retryAfterMs, $errBody);
                }

                // OMS-98 - 503 wallet_unavailable stays in the generic
                // (retryable) path; construct the specific class so a
                // retry-exhausted failure surfaces as WalletUnavailableException.
                $sdkException = ($status === 503 && $reason === 'wallet_unavailable')
                    ? new WalletUnavailableException($status, $message, $reason, $remainingQuota, $retryAfterMs, $errBody)
                    : new OrbotoMailException($status, $message, $reason, $remainingQuota, $retryAfterMs, $errBody);

                if ($sdkException->isRetryable() && $attempt < $this->maxRetries) {
                    $lastError = $sdkException;
                    $this->sleepWithBackoff($attempt, $retryAfterMs);
                    continue;
                }

                throw $sdkException;
            } catch (ClientExceptionInterface $e) {
                if ($attempt < $this->maxRetries) {
                    $lastError = $e;
                    $this->sleepWithBackoff($attempt, null);
                    continue;
                }
                throw new OrbotoMailException(0, 'OMS network error: ' . $e->getMessage(), null, null, null, null);
            } catch (OrbotoMailException $e) {
                throw $e;
            } catch (Throwable $e) {
                throw new OrbotoMailException(0, 'OMS request error: ' . $e->getMessage(), null, null, null, null);
            }
        }

        if ($lastError instanceof Throwable) {
            throw $lastError instanceof OrbotoMailException
                ? $lastError
                : new OrbotoMailException(0, 'OMS request failed after retries: ' . $lastError->getMessage(), null, null, null, null);
        }
        throw new OrbotoMailException(0, 'OMS request failed after retries', null, null, null, null);
    }

    private function sleepWithBackoff(int $attempt, ?int $retryAfterMs): void
    {
        // Honor server retry-after when present; otherwise exponential
        // backoff with jitter: 100 ms, 200 ms, 400 ms, 800 ms… (capped 8 s).
        $base = $retryAfterMs ?? min(8_000, 100 * (2 ** $attempt));
        $jitter = (int) (random_int(0, 250) / 1000.0 * $base);
        usleep(($base + $jitter) * 1000);
    }
}
