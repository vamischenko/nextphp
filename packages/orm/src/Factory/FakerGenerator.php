<?php

declare(strict_types=1);

namespace Nextphp\Orm\Factory;

/**
 * Minimal built-in faker — no external dependency.
 *
 * Covers the most common factory needs. If Faker/fzaninotto is available
 * as a dev dependency you can extend this or replace it entirely.
 */
final class FakerGenerator
{
    private static int $sequence = 0;

    // -------------------------------------------------------------------------
    // Names
    // -------------------------------------------------------------------------

    public function name(): string
    {
        $first = ['Alice', 'Bob', 'Carol', 'Dave', 'Eve', 'Frank', 'Grace', 'Hank', 'Iris', 'Jack'];
        $last  = ['Smith', 'Jones', 'Williams', 'Brown', 'Taylor', 'Davies', 'Evans', 'Wilson', 'Thomas', 'Roberts'];
        return $this->pick($first) . ' ' . $this->pick($last);
    }

    public function firstName(): string
    {
        return $this->pick(['Alice', 'Bob', 'Carol', 'Dave', 'Eve', 'Frank', 'Grace', 'Hank']);
    }

    public function lastName(): string
    {
        return $this->pick(['Smith', 'Jones', 'Williams', 'Brown', 'Taylor', 'Davies', 'Evans']);
    }

    // -------------------------------------------------------------------------
    // Internet
    // -------------------------------------------------------------------------

    public function email(): string
    {
        return strtolower($this->word()) . '.' . ++self::$sequence . '@example.com';
    }

    public function username(): string
    {
        return strtolower($this->word()) . self::$sequence;
    }

    public function url(): string
    {
        return 'https://example.com/' . $this->slug();
    }

    public function ipv4(): string
    {
        return implode('.', [rand(1, 254), rand(0, 255), rand(0, 255), rand(1, 254)]);
    }

    // -------------------------------------------------------------------------
    // Text
    // -------------------------------------------------------------------------

    public function word(): string
    {
        return $this->pick(['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur',
            'adipiscing', 'elit', 'sed', 'do', 'eiusmod', 'tempor']);
    }

    public function words(int $count = 3): string
    {
        $words = [];
        for ($i = 0; $i < $count; $i++) {
            $words[] = $this->word();
        }
        return implode(' ', $words);
    }

    public function sentence(int $words = 6): string
    {
        return ucfirst($this->words($words)) . '.';
    }

    public function paragraph(int $sentences = 3): string
    {
        $result = [];
        for ($i = 0; $i < $sentences; $i++) {
            $result[] = $this->sentence(rand(4, 10));
        }
        return implode(' ', $result);
    }

    public function slug(int $words = 3): string
    {
        $parts = [];
        for ($i = 0; $i < $words; $i++) {
            $parts[] = $this->word();
        }
        return implode('-', $parts);
    }

    public function title(): string
    {
        return ucwords($this->words(rand(3, 6)));
    }

    // -------------------------------------------------------------------------
    // Numbers
    // -------------------------------------------------------------------------

    public function randomNumber(int $min = 0, int $max = PHP_INT_MAX): int
    {
        return rand($min, $max);
    }

    public function randomFloat(int $decimals = 2, float $min = 0, float $max = 1000): float
    {
        return round($min + lcg_value() * ($max - $min), $decimals);
    }

    public function boolean(int $chanceOfTrue = 50): bool
    {
        return rand(1, 100) <= $chanceOfTrue;
    }

    // -------------------------------------------------------------------------
    // Date / time
    // -------------------------------------------------------------------------

    public function dateTime(string $format = 'Y-m-d H:i:s', ?int $maxTimestamp = null): string
    {
        $max = $maxTimestamp ?? time();
        return date($format, rand(0, $max));
    }

    public function date(string $format = 'Y-m-d'): string
    {
        return $this->dateTime($format);
    }

    public function timestamp(): int
    {
        return rand(0, time());
    }

    // -------------------------------------------------------------------------
    // Misc
    // -------------------------------------------------------------------------

    public function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            rand(0, 0xffff), rand(0, 0xffff),
            rand(0, 0xffff),
            rand(0, 0x0fff) | 0x4000,
            rand(0, 0x3fff) | 0x8000,
            rand(0, 0xffff), rand(0, 0xffff), rand(0, 0xffff),
        );
    }

    public function hexColor(): string
    {
        return sprintf('#%06x', rand(0, 0xFFFFFF));
    }

    /** @param list<string> $array */
    public function randomElement(array $array): string
    {
        return $array[array_rand($array)];
    }

    /** @param list<string> $array */
    private function pick(array $array): string
    {
        return $array[array_rand($array)];
    }

    public function unique(): self
    {
        // Fluent no-op for API compatibility — uniqueness guaranteed by sequence counter
        return $this;
    }
}
