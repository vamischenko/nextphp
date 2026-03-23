<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection;

/**
 * Default HttpClientInterface implementation using PHP cURL.
 */
final class CurlHttpClient implements HttpClientInterface
{
    public function post(string $url, string $body): string
    {
        $ch = curl_init($url);

        if ($ch === false) {
            throw new \RuntimeException('Failed to initialize cURL.');
        }

        curl_setopt_array($ch, [
            \CURLOPT_POST           => true,
            \CURLOPT_POSTFIELDS     => $body,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_HTTPHEADER     => ['Content-Type: text/plain'],
            \CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error !== '') {
            throw new \RuntimeException('ClickHouse HTTP request failed: ' . $error);
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException(
                sprintf('ClickHouse returned HTTP %d: %s', $httpCode, (string) $response),
            );
        }

        return (string) $response;
    }
}
