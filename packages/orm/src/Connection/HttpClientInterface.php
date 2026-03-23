<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection;

/**
 * Minimal HTTP client contract used by ClickHouseConnection.
 * Allows replacing the transport in tests without real network calls.
 */
interface HttpClientInterface
{
    /**
     * Send a POST request and return the response body.
     *
     * @throws \RuntimeException on connection failure
     */
    public function post(string $url, string $body): string;
}
