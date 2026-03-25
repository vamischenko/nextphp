<?php

declare(strict_types=1);

namespace Nextphp\Debugbar\Collector;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Collects basic HTTP request info: method, URI, headers, server params.
 */
final class RequestCollector implements CollectorInterface
{
    public function __construct(private readonly ServerRequestInterface $request)
    {
    }

    public function getName(): string
    {
        return 'request';
    }

    public function collect(): array
    {
        $headers = [];
        foreach ($this->request->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        return [
            'method'  => $this->request->getMethod(),
            'uri'     => (string) $this->request->getUri(),
            'headers' => $headers,
        ];
    }
}
