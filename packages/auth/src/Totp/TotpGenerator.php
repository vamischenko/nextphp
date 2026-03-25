<?php

declare(strict_types=1);

namespace Nextphp\Auth\Totp;

/**
 * RFC 6238 TOTP (Time-Based One-Time Password) implementation.
 * No external dependencies — uses hash_hmac(SHA1) as per the spec.
 *
 * Secrets must be Base32-encoded strings (standard Google Authenticator format).
 */
final class TotpGenerator
{
    private const DIGITS   = 6;
    private const PERIOD   = 30;  // seconds per time-step
    private const WINDOW   = 1;   // allow ±1 step to account for clock skew

    public function __construct(
        private readonly int $digits = self::DIGITS,
        private readonly int $period = self::PERIOD,
        private readonly int $window = self::WINDOW,
    ) {
    }

    /** Generate a random 160-bit secret, Base32-encoded. */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    /**
     * Generate the current TOTP code for the given secret.
     * Optionally supply $timestamp (defaults to time()).
     */
    public function generate(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $counter = intdiv($timestamp, $this->period);

        return $this->hotp($secret, $counter);
    }

    /**
     * Verify a code within the drift window.
     */
    public function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        $timestamp ??= time();
        $counter = intdiv($timestamp, $this->period);

        for ($offset = -$this->window; $offset <= $this->window; $offset++) {
            if (hash_equals($this->hotp($secret, $counter + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a otpauth:// URI for QR-code generators (Google Authenticator, Authy, …).
     */
    public function getUri(string $secret, string $account, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s%%3A%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($account),
            $secret,
            rawurlencode($issuer),
            $this->digits,
            $this->period,
        );
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function hotp(string $secret, int $counter): string
    {
        $key     = $this->base32Decode($secret);
        $message = pack('J', $counter); // 64-bit big-endian

        $hash   = hash_hmac('sha1', $message, $key, true);
        $offset = ord($hash[19]) & 0x0F;

        $code = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
            ( ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** $this->digits);

        return str_pad((string) $code, $this->digits, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output   = '';
        $buffer   = 0;
        $bitsLeft = 0;

        foreach (str_split($data) as $char) {
            $buffer   = ($buffer << 8) | ord($char);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $output  .= $alphabet[($buffer >> ($bitsLeft - 5)) & 0x1F];
                $bitsLeft -= 5;
            }
        }

        if ($bitsLeft > 0) {
            $output .= $alphabet[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $output;
    }

    private function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data     = strtoupper(rtrim($data, '='));
        $output   = '';
        $buffer   = 0;
        $bitsLeft = 0;

        foreach (str_split($data) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $buffer   = ($buffer << 5) | $pos;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $output  .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }

        return $output;
    }
}
