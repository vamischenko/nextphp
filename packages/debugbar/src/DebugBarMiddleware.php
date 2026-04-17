<?php

declare(strict_types=1);

namespace Nextphp\Debugbar;

use Nextphp\Debugbar\Collector\RequestCollector;
use Nextphp\Debugbar\Renderer\HtmlRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware that injects the debug bar HTML before </body> in HTML responses.
 *
 * Only active when DebugBar::isEnabled() returns true.
 * Skipped for non-HTML responses (JSON, redirects, etc.).
 */
final class DebugBarMiddleware implements MiddlewareInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly DebugBar $bar,
        private readonly HtmlRenderer $renderer = new HtmlRenderer(),
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->bar->isEnabled()) {
            return $handler->handle($request);
        }

        // Register request collector for this request
        $this->bar->addCollector(new RequestCollector($request));

        $response = $handler->handle($request);

        $contentType = $response->getHeaderLine('Content-Type');

        // Only inject into HTML responses
        if (!str_contains($contentType, 'text/html') && $contentType !== '') {
            return $response;
        }

        $body    = (string) $response->getBody();
        $barHtml = $this->renderer->render($this->bar);

        // Inject before </body> if present, otherwise append
        if (stripos($body, '</body>') !== false) {
            $body = str_ireplace('</body>', $barHtml . '</body>', $body);
        } else {
            $body .= $barHtml;
        }

        $response->getBody()->rewind();

        // Build new response with modified body
        // We write to the original stream and update Content-Length if it was set
        $stream = $response->getBody();
        $stream->rewind();

        // Use a simple in-memory stream replacement approach
        $newBody = new \Nextphp\Debugbar\Stream\StringStream($body);

        $newResponse = $response->withBody($newBody);

        if ($newResponse->hasHeader('Content-Length')) {
            $newResponse = $newResponse->withHeader('Content-Length', (string) strlen($body));
        }

        return $newResponse;
    }
}
