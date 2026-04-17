<?php

declare(strict_types=1);

namespace Nextphp\Http\Middleware;

use Nextphp\Http\Message\Response;
use Nextphp\Http\Message\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DebugbarMiddleware implements MiddlewareInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly bool $injectHtml = true,
        private readonly bool $addServerTiming = true,
        private readonly string $headerPrefix = 'X-Nextphp-Debug',
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = hrtime(true);
        $startMem = memory_get_usage(true);

        $response = $handler->handle($request);

        $elapsedMs = (hrtime(true) - $start) / 1_000_000;
        $peakMem = memory_get_peak_usage(true);

        $response = $response
            ->withHeader($this->headerPrefix . '-Time-Ms', number_format($elapsedMs, 3, '.', ''))
            ->withHeader($this->headerPrefix . '-Memory-Start', (string) $startMem)
            ->withHeader($this->headerPrefix . '-Memory-Peak', (string) $peakMem);

        if ($this->addServerTiming) {
            $response = $response->withHeader(
                'Server-Timing',
                sprintf('app;dur=%s', number_format($elapsedMs, 3, '.', '')),
            );
        }

        if (!$this->injectHtml) {
            return $response;
        }

        $contentType = $response->getHeaderLine('Content-Type');
        if ($contentType === '' || stripos($contentType, 'text/html') === false) {
            return $response;
        }

        $body = (string) $response->getBody();
        if ($body === '' || stripos($body, '</body>') === false) {
            return $response;
        }

        $snippet = $this->renderToolbar($elapsedMs, $peakMem);
        $body = preg_replace('~</body>~i', $snippet . "\n</body>", $body, 1) ?? $body;

        // If it's already our Response, keep status/headers and replace body safely.
        if ($response instanceof Response) {
            return $response->withBody(Stream::fromString($body));
        }

        return (new Response(
            statusCode: $response->getStatusCode(),
            headers: $response->getHeaders(),
            body: $body,
            version: $response->getProtocolVersion(),
            reason: method_exists($response, 'getReasonPhrase') ? $response->getReasonPhrase() : '',
        ));
    }

    /**
      * @psalm-pure
     */
    private function renderToolbar(float $elapsedMs, int $peakMem): string
    {
        $memMb = $peakMem / (1024 * 1024);

        return sprintf(
            <<<'HTML'
                <div style="position:fixed;left:0;right:0;bottom:0;z-index:2147483647;background:#111827;color:#e5e7eb;font:12px/1.4 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;padding:8px 12px;box-shadow:0 -6px 24px rgba(0,0,0,.25);">
                  <span style="font-weight:700;">Nextphp Debugbar</span>
                  <span style="margin-left:12px;">time: <b>%s ms</b></span>
                  <span style="margin-left:12px;">peak mem: <b>%s MB</b></span>
                </div>
                HTML,
            number_format($elapsedMs, 3, '.', ''),
            number_format($memMb, 2, '.', ''),
        );
    }
}
