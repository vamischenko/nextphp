<?php

declare(strict_types=1);

namespace Nextphp\Mail\Http;

/**
 * Minimal HTTP client abstraction used by API-based mail drivers.
 * Allows mocking in tests without ext-curl or HTTP extensions.
 */
interface HttpClientInterface
{
    /**
     * Send an HTTP POST request.
     *
     * @param array<string, string> $headers
     * @param string                $body    Raw request body
     * @return array{status: int, body: string}
     */
    public function post(string $url, array $headers, string $body): array;
}
