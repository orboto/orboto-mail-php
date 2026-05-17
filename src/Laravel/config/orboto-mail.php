<?php

declare(strict_types=1);

/**
 * Orboto Mail Service — Laravel configuration.
 *
 * Publish with:
 *   php artisan vendor:publish --tag=orboto-mail-config
 *
 * Env vars (recommended for production):
 *   OMS_API_KEY      — your `oms_live_*` or `oms_test_*` API key
 *   OMS_BASE_URL     — override the default `https://mail.orboto.io/api`
 *   OMS_TIMEOUT      — per-request timeout in seconds (default 10)
 *   OMS_MAX_RETRIES  — transient-error retry budget (default 3)
 */
return [
    'api_key' => env('OMS_API_KEY'),
    'base_url' => env('OMS_BASE_URL', 'https://mail.orboto.io/api'),
    'timeout' => (float) env('OMS_TIMEOUT', 10.0),
    'max_retries' => (int) env('OMS_MAX_RETRIES', 3),
];
