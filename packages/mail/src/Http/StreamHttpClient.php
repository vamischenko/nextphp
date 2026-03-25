<?php

declare(strict_types=1);

namespace Nextphp\Mail\Http;

use RuntimeException;

/**
 * Production HTTP client backed by PHP stream contexts (no ext-curl needed).
 */
final class StreamHttpClient implements HttpClientInterface
{
    public function post(string $url, array $headers, string $body): array
    {
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = "{$name}: {$value}";
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", $headerLines),
                'content' => $body,
                'ignore_errors' => true,
            ],
        ]);

        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            throw new RuntimeException("HTTP POST to {$url} failed.");
        }

        // Parse status from $http_response_header (set by file_get_contents)
        $status = 200;
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $m);
            $status = (int) ($m[1] ?? 200);
        }

        return ['status' => $status, 'body' => $response];
    }
}
