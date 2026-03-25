<?php

declare(strict_types=1);

namespace Nextphp\Auth\Tests\Unit;

use Nextphp\Auth\Jwt\JwtEncoder;
use Nextphp\Auth\Jwt\JwtException;
use Nextphp\Auth\Jwt\JwtGuard;
use Nextphp\Auth\UserInterface;
use Nextphp\Auth\UserProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(JwtEncoder::class)]
#[CoversClass(JwtGuard::class)]
#[CoversClass(JwtException::class)]
final class JwtGuardTest extends TestCase
{
    private JwtEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new JwtEncoder('super-secret-key');
    }

    // -------------------------------------------------------------------------
    // JwtEncoder
    // -------------------------------------------------------------------------

    #[Test]
    public function encodeThenDecodeRoundtrip(): void
    {
        $payload  = ['sub' => '42', 'role' => 'admin', 'exp' => time() + 3600];
        $token    = $this->encoder->encode($payload);
        $decoded  = $this->encoder->decode($token);

        self::assertSame('42', $decoded['sub']);
        self::assertSame('admin', $decoded['role']);
    }

    #[Test]
    public function decodeThrowsOnTamperedSignature(): void
    {
        $token  = $this->encoder->encode(['sub' => '1', 'exp' => time() + 60]);
        $parts  = explode('.', $token);
        $parts[2] = 'tampered';

        $this->expectException(JwtException::class);
        $this->encoder->decode(implode('.', $parts));
    }

    #[Test]
    public function decodeThrowsOnExpiredToken(): void
    {
        $token = $this->encoder->encode(['sub' => '1', 'exp' => time() - 1]);

        $this->expectException(JwtException::class);
        $this->encoder->decode($token);
    }

    #[Test]
    public function decodeThrowsOnInvalidFormat(): void
    {
        $this->expectException(JwtException::class);
        $this->encoder->decode('not.a.valid.jwt.here');
    }

    #[Test]
    public function differentSecretsProduceDifferentSignatures(): void
    {
        $encoder2 = new JwtEncoder('other-secret');
        $token    = $this->encoder->encode(['sub' => '1']);

        $this->expectException(JwtException::class);
        $encoder2->decode($token);
    }

    // -------------------------------------------------------------------------
    // JwtGuard
    // -------------------------------------------------------------------------

    #[Test]
    public function issueTokenAndValidateViaHeader(): void
    {
        $user     = $this->makeUser('42');
        $provider = $this->createMock(UserProviderInterface::class);
        $provider->method('findByCredentials')->with('42')->willReturn($user);

        $guard  = new JwtGuard($provider, $this->encoder);
        $token  = $guard->issueToken($user);
        $result = $guard->fromHeader('Bearer ' . $token);

        self::assertSame($user, $result);
    }

    #[Test]
    public function fromHeaderReturnsNullWithoutBearerPrefix(): void
    {
        $provider = $this->createMock(UserProviderInterface::class);
        $guard    = new JwtGuard($provider, $this->encoder);

        self::assertNull($guard->fromHeader('Basic abc'));
    }

    #[Test]
    public function fromHeaderReturnsNullOnInvalidToken(): void
    {
        $provider = $this->createMock(UserProviderInterface::class);
        $guard    = new JwtGuard($provider, $this->encoder);

        self::assertNull($guard->fromHeader('Bearer invalid.token.here'));
    }

    #[Test]
    public function fromHeaderReturnsNullWhenUserNotFound(): void
    {
        $provider = $this->createMock(UserProviderInterface::class);
        $provider->method('findByCredentials')->willReturn(null);

        $guard = new JwtGuard($provider, $this->encoder);
        $user  = $this->makeUser('99');
        $token = $guard->issueToken($user);

        self::assertNull($guard->fromHeader('Bearer ' . $token));
    }

    private function makeUser(string $id): UserInterface
    {
        return new class ($id) implements UserInterface {
            public function __construct(private readonly string $id)
            {
            }

            public function getAuthIdentifier(): string|int
            {
                return $this->id;
            }

            public function getAuthPasswordHash(): string
            {
                return '';
            }
        };
    }
}
