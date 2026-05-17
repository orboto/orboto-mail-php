<?php

declare(strict_types=1);

namespace Orboto\Mail\Resource;

use Orboto\Mail\Dto\CloudflareAutoSetupResult;
use Orboto\Mail\Dto\CloudflareDetectResult;
use Orboto\Mail\Http\HttpClient;

final class SenderDomainsResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Detect whether a sender-domain is hosted on Cloudflare DNS.
     * The UI uses the result to conditionally show the "Auto-setup
     * via Cloudflare" button.
     */
    public function cloudflareDetect(string $domainId): CloudflareDetectResult
    {
        $response = $this->http->request('GET', '/v1/sender-domains/' . rawurlencode($domainId) . '/cloudflare-detect');
        return CloudflareDetectResult::fromArray($response ?? []);
    }

    /**
     * Auto-setup the DKIM + SPF + DMARC records on Cloudflare using a
     * customer-supplied API token. Default behaviour is single-use:
     * the token validates, creates the 5 records, then is discarded.
     * Pass `storeForRotation: true` to AES-256-GCM-encrypt + retain
     * the token for future DKIM-key rotations.
     *
     * @param array{apiToken: string, storeForRotation?: bool} $input
     */
    public function cloudflareAutoSetup(string $domainId, array $input): CloudflareAutoSetupResult
    {
        $response = $this->http->request('POST', '/v1/sender-domains/' . rawurlencode($domainId) . '/cloudflare-auto-setup', $input);
        return CloudflareAutoSetupResult::fromArray($response ?? []);
    }
}
