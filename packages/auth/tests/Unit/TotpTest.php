<?php

declare(strict_types=1);

namespace Nextphp\Auth\Tests\Unit;

use Nextphp\Auth\Totp\TotpGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TotpGenerator::class)]
final class TotpTest extends TestCase
{
    private TotpGenerator $totp;

    protected function setUp(): void
    {
        $this->totp = new TotpGenerator();
    }

    #[Test]
    public function generateSecretReturnsSufficientlyLongBase32String(): void
    {
        $secret = $this->totp->generateSecret();

        // 20 bytes base32-encoded → 32 chars minimum
        self::assertGreaterThanOrEqual(32, strlen($secret));
        self::assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    #[Test]
    public function generateReturnsSixDigitCode(): void
    {
        $secret = $this->totp->generateSecret();
        $code   = $this->totp->generate($secret);

        self::assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    #[Test]
    public function verifyAcceptsCurrentCode(): void
    {
        $secret = $this->totp->generateSecret();
        $code   = $this->totp->generate($secret);

        self::assertTrue($this->totp->verify($secret, $code));
    }

    #[Test]
    public function verifyRejectsBadCode(): void
    {
        $secret = $this->totp->generateSecret();

        self::assertFalse($this->totp->verify($secret, '000000'));
    }

    #[Test]
    public function verifyAcceptsPreviousStepWithinWindow(): void
    {
        $secret    = $this->totp->generateSecret();
        $now       = time();
        $prevCode  = $this->totp->generate($secret, $now - 30);

        // Should be accepted within the ±1 window
        self::assertTrue($this->totp->verify($secret, $prevCode, $now));
    }

    #[Test]
    public function verifyRejectsCodeOutsideWindow(): void
    {
        $totp   = new TotpGenerator(window: 0);
        $secret = $totp->generateSecret();
        $now    = time();

        $oldCode = $totp->generate($secret, $now - 60);

        self::assertFalse($totp->verify($secret, $oldCode, $now));
    }

    #[Test]
    public function sameSecretSameTimestampSameCode(): void
    {
        $secret = $this->totp->generateSecret();
        $ts     = time();

        self::assertSame(
            $this->totp->generate($secret, $ts),
            $this->totp->generate($secret, $ts),
        );
    }

    #[Test]
    public function getUriContainsRequiredParts(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $uri    = $this->totp->getUri($secret, 'user@example.com', 'MyApp');

        self::assertStringStartsWith('otpauth://totp/', $uri);
        self::assertStringContainsString($secret, $uri);
        self::assertStringContainsString('MyApp', $uri);
    }

    #[Test]
    public function customDigitCount(): void
    {
        $totp   = new TotpGenerator(digits: 8);
        $secret = $totp->generateSecret();
        $code   = $totp->generate($secret);

        self::assertMatchesRegularExpression('/^\d{8}$/', $code);
        self::assertTrue($totp->verify($secret, $code));
    }

    #[Test]
    public function knownVector(): void
    {
        // RFC 6238 test vector: secret = "12345678901234567890" (ASCII), T=1, period=30
        // Counter = floor(59/30) = 1 => HOTP(counter=1)
        // Expected code: 287082
        $secret  = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // base32("12345678901234567890")
        $ts      = 59;

        $code = $this->totp->generate($secret, $ts);
        self::assertSame('287082', $code);
    }
}
