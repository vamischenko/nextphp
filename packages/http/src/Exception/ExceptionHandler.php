<?php

declare(strict_types=1);

namespace Nextphp\Http\Exception;

use Nextphp\Http\Message\Response;
use Nextphp\Routing\Exception\MethodNotAllowedException as RoutingMethodNotAllowedException;
use Nextphp\Routing\Exception\RouteNotFoundException as RoutingRouteNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class ExceptionHandler
{
    public function __construct(
        private readonly bool $debug = false,
    ) {
    }

    public function handle(Throwable $exception, ?ServerRequestInterface $request = null): ResponseInterface
    {
        $status = match (true) {
            $exception instanceof HttpException => $exception->getStatusCode(),
            $exception instanceof RoutingRouteNotFoundException => 404,
            $exception instanceof RoutingMethodNotAllowedException => 405,
            default => 500,
        };

        $accept    = $request?->getHeaderLine('Accept') ?? '';
        $wantsJson = str_contains($accept, 'application/json');

        if ($wantsJson) {
            $data = ['error' => $exception->getMessage(), 'status' => $status];

            if ($this->debug) {
                $data['trace'] = $exception->getTraceAsString();
                $data['class'] = $exception::class;
            }

            return Response::json($data, $status);
        }

        $body = $this->debug
            ? $this->renderDebugPage($exception, $status)
            : $this->renderSimplePage($exception->getMessage(), $status);

        return new Response($status, ['Content-Type' => 'text/html; charset=UTF-8'], $body);
    }

    private function renderSimplePage(string $message, int $status): string
    {
        $title   = htmlspecialchars($this->statusTitle($status), ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"><title>{$status} {$title}</title>
        <style>body{font-family:sans-serif;background:#f5f5f5;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
        .box{background:#fff;padding:2rem 3rem;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.1);text-align:center}
        h1{font-size:4rem;margin:0;color:#c0392b}p{color:#555}</style>
        </head>
        <body><div class="box"><h1>{$status}</h1><p>{$message}</p></div></body>
        </html>
        HTML;
    }

    private function renderDebugPage(Throwable $exception, int $status): string
    {
        $title   = htmlspecialchars($status . ' ' . $this->statusTitle($status), ENT_QUOTES, 'UTF-8');
        $class   = htmlspecialchars($exception::class, ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
        $file    = htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8');
        $line    = $exception->getLine();
        $trace   = htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8');

        $previous = '';
        $prev = $exception->getPrevious();
        while ($prev !== null) {
            $prevClass   = htmlspecialchars($prev::class, ENT_QUOTES, 'UTF-8');
            $prevMessage = htmlspecialchars($prev->getMessage(), ENT_QUOTES, 'UTF-8');
            $prevFile    = htmlspecialchars($prev->getFile(), ENT_QUOTES, 'UTF-8');
            $prevLine    = $prev->getLine();
            $prevTrace   = htmlspecialchars($prev->getTraceAsString(), ENT_QUOTES, 'UTF-8');
            $previous   .= <<<HTML

            <div class="previous">
              <h3>Caused by: <span class="class">{$prevClass}</span></h3>
              <p class="message">{$prevMessage}</p>
              <p class="location">{$prevFile}:{$prevLine}</p>
              <pre class="trace">{$prevTrace}</pre>
            </div>
            HTML;
            $prev = $prev->getPrevious();
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="UTF-8">
        <title>{$title}</title>
        <style>
          *{box-sizing:border-box}
          body{margin:0;font-family:'Segoe UI',sans-serif;background:#1e1e2e;color:#cdd6f4}
          header{background:#c0392b;color:#fff;padding:1.2rem 2rem}
          header h1{margin:0;font-size:1.4rem}
          header .class{font-size:1rem;opacity:.8}
          main{padding:2rem;max-width:1100px;margin:0 auto}
          .card{background:#2a2a3d;border-radius:8px;padding:1.5rem;margin-bottom:1.5rem}
          .card h2{margin:0 0 .8rem;font-size:1rem;text-transform:uppercase;letter-spacing:.05em;color:#89b4fa}
          .message{font-size:1.25rem;color:#f38ba8;margin:0 0 .5rem}
          .location{color:#a6adc8;font-size:.9rem;margin:0}
          pre.trace{background:#181825;padding:1rem;border-radius:6px;overflow-x:auto;font-size:.8rem;line-height:1.5;color:#cdd6f4;margin:0}
          .previous{border-left:3px solid #fab387;padding-left:1rem;margin-top:1rem}
          .previous h3{margin:0 0 .4rem;color:#fab387;font-size:.95rem}
          .request{display:grid;grid-template-columns:max-content 1fr;gap:.3rem 1.2rem;font-size:.9rem}
          .request span:nth-child(odd){color:#89b4fa;font-weight:600}
        </style>
        </head>
        <body>
        <header>
          <h1>{$message}</h1>
          <div class="class">{$class} &bull; HTTP {$status}</div>
        </header>
        <main>
          <div class="card">
            <h2>Location</h2>
            <p class="location">{$file}:{$line}</p>
          </div>
          <div class="card">
            <h2>Stack Trace</h2>
            <pre class="trace">{$trace}</pre>
            {$previous}
          </div>
        </main>
        </body>
        </html>
        HTML;
    }

    private function statusTitle(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }
}
