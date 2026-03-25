<?php

declare(strict_types=1);

namespace Nextphp\Auth\Jwt;

/**
 * Minimal HS256 JWT encoder / decoder with no external dependencies.
 */
final class JwtEncoder
{
    public function __construct(private readonly string $secret)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function encode(array $payload): string
    {
        $header  = $this->base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $body    = $this->base64url(json_encode($payload, JSON_THROW_ON_ERROR));
        $sig     = $this->base64url($this->sign("{$header}.{$body}"));

        return "{$header}.{$body}.{$sig}";
    }

    /**
     * Decode and verify a JWT token.
     *
     * @return array<string, mixed>
     * @throws JwtException on invalid format, signature mismatch, or expiry
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new JwtException('Invalid JWT format.');
        }

        [$header, $body, $sig] = $parts;

        $expected = $this->base64url($this->sign("{$header}.{$body}"));
        if (! hash_equals($expected, $sig)) {
            throw new JwtException('JWT signature mismatch.');
        }

        $raw = base64_decode(strtr($body, '-_', '+/'), strict: true);
        if ($raw === false) {
            throw new JwtException('Invalid JWT base64 encoding.');
        }

        $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($payload)) {
            throw new JwtException('Invalid JWT payload.');
        }

        if (isset($payload['exp']) && (int) $payload['exp'] < time()) {
            throw new JwtException('JWT has expired.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    private function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret, binary: true);
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
