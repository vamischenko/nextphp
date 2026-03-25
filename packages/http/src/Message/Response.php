<?php

declare(strict_types=1);

namespace Nextphp\Http\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    use MessageTrait;

    private int $statusCode;

    private string $reasonPhrase;

    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $version = '1.1',
        string $reason = '',
    ) {
        $this->statusCode = $statusCode;
        $this->reasonPhrase = $reason !== '' ? $reason : HttpStatus::phraseFor($statusCode);
        $this->setHeaders($headers);
        $this->protocolVersion = $version;

        if ($body === null) {
            $this->body = Stream::fromString();
        } elseif (is_string($body)) {
            $this->body = Stream::fromString($body);
        } else {
            $this->body = $body;
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase !== '' ? $reasonPhrase : HttpStatus::phraseFor($code);

        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Convenience: create JSON response.
     *
     * @param mixed[] $data
     */
    public static function json(array $data, int $status = 200): self
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return new self(
            statusCode: $status,
            headers: ['Content-Type' => 'application/json'],
            body: $json,
        );
    }
}
