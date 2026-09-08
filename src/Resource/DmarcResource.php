<?php

declare(strict_types=1);

namespace Orboto\Mail\Resource;

use Orboto\Mail\Http\HttpClient;

/**
 * DMARC aggregate-report queries per sender domain (OMS-48).
 *
 * Every call is scoped to the API key's account: a domain you do not
 * own answers 404 `domain_not_found`. Receivers (Gmail, Outlook, ...)
 * batch reports daily, so a fresh domain shows an empty state for the
 * first 24-48h.
 */
final class DmarcResource
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Aggregate auth-pass rate, dispositions, top reporting orgs and top
     * source IPs over the period (`7d`, `30d` default, `90d` max).
     *
     * @return array{
     *   domain: string,
     *   period: string,
     *   totalReports: int,
     *   summary: ?array{
     *     totalMessages: int,
     *     passedMessages: int,
     *     authPassRate: ?float,
     *     dispositions: array{none: int, quarantine: int, reject: int},
     *     topReportingOrgs: array<int, array{orgName: string, messages: int, reports: int}>,
     *     topSourceIps: array<int, array{sourceIp: string, messages: int, passedMessages: int, dkimAlignedMessages: int, spfAlignedMessages: int}>
     *   }
     * }
     */
    public function summary(string $domain, ?string $period = null): array
    {
        $query = $period !== null ? '?period=' . rawurlencode($period) : '';
        return $this->http->request('GET', '/v1/dmarc/domains/' . rawurlencode($domain) . '/summary' . $query) ?? [];
    }

    /**
     * Report envelopes for a domain, most recent first, cursor-paginated.
     *
     * @param array{limit?: int, cursor?: string} $options
     * @return array{reports: array<int, array<string, mixed>>, nextCursor: ?string}
     */
    public function reports(string $domain, array $options = []): array
    {
        $params = array_filter([
            'limit' => isset($options['limit']) ? (string) $options['limit'] : null,
            'cursor' => $options['cursor'] ?? null,
        ], fn ($v) => $v !== null);
        $query = $params === [] ? '' : '?' . http_build_query($params);
        $response = $this->http->request('GET', '/v1/dmarc/domains/' . rawurlencode($domain) . '/reports' . $query) ?? [];
        return [
            'reports' => $response['reports'] ?? [],
            'nextCursor' => $response['nextCursor'] ?? null,
        ];
    }

    /**
     * One report with all its per-source-IP records.
     *
     * @return array<string, mixed>
     */
    public function report(string $id): array
    {
        return $this->http->request('GET', '/v1/dmarc/reports/' . rawurlencode($id)) ?? [];
    }

    /**
     * Per-source-IP breakdown over the period: volume, alignment,
     * dispositions and the header-from domains each IP used.
     *
     * @param array{period?: string, limit?: int, cursor?: string} $options
     * @return array{domain: string, period: string, sourceIps: array<int, array<string, mixed>>, nextCursor: ?string}
     */
    public function sourceIps(string $domain, array $options = []): array
    {
        $params = array_filter([
            'period' => $options['period'] ?? null,
            'limit' => isset($options['limit']) ? (string) $options['limit'] : null,
            'cursor' => $options['cursor'] ?? null,
        ], fn ($v) => $v !== null);
        $query = $params === [] ? '' : '?' . http_build_query($params);
        $response = $this->http->request('GET', '/v1/dmarc/domains/' . rawurlencode($domain) . '/source-ips' . $query) ?? [];
        return [
            'domain' => $response['domain'] ?? $domain,
            'period' => $response['period'] ?? ($options['period'] ?? '30d'),
            'sourceIps' => $response['sourceIps'] ?? [],
            'nextCursor' => $response['nextCursor'] ?? null,
        ];
    }
}
