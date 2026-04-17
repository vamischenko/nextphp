<?php

declare(strict_types=1);

namespace Nextphp\Console\Schedule;

/**
 * Minimal cron expression matcher.
 *
 * Supports:
 *   *        — wildcard
 *   5        — exact value
 *   1,3,5    — list
 *   1-5      — range
 *   * /5     — step (every N)
 *   1-30/5   — range with step
 *
 * Expression format: "minute hour day-of-month month day-of-week"
 */
final class CronExpression
{
    public static function matches(string $expression, \DateTimeInterface $time): bool
    {
        $parts = preg_split('/\s+/', trim($expression));

        if ($parts === false || count($parts) !== 5) {
            return false;
        }

        [$minute, $hour, $dom, $month, $dow] = $parts;

        return self::matchField($minute, (int) $time->format('i'), 0, 59)
            && self::matchField($hour,   (int) $time->format('G'), 0, 23)
            && self::matchField($dom,    (int) $time->format('j'), 1, 31)
            && self::matchField($month,  (int) $time->format('n'), 1, 12)
            && self::matchField($dow,    (int) $time->format('w'), 0, 6);
    }

    /**
     * @psalm-pure
     */
    private static function matchField(string $field, int $value, int $min, int $max): bool
    {
        // Handle comma-separated list: "1,3,5"
        if (str_contains($field, ',')) {
            foreach (explode(',', $field) as $part) {
                if (self::matchPart(trim($part), $value, $min, $max)) {
                    return true;
                }
            }
            return false;
        }

        return self::matchPart($field, $value, $min, $max);
    }

    /**
     * @psalm-pure
     */
    private static function matchPart(string $part, int $value, int $min, int $max): bool
    {
        // Step: "*/5" or "1-30/5"
        if (str_contains($part, '/')) {
            [$range, $step] = explode('/', $part, 2);
            $step = (int) $step;
            if ($step <= 0) {
                return false;
            }
            [$rangeMin, $rangeMax] = self::parseRange($range, $min, $max);
            if ($value < $rangeMin || $value > $rangeMax) {
                return false;
            }
            return ($value - $rangeMin) % $step === 0;
        }

        // Range: "1-5"
        if (str_contains($part, '-')) {
            [$rangeMin, $rangeMax] = self::parseRange($part, $min, $max);
            return $value >= $rangeMin && $value <= $rangeMax;
        }

        // Wildcard
        if ($part === '*') {
            return true;
        }

        // Exact value
        return is_numeric($part) && (int) $part === $value;
    }

    /**
     * @return array{int, int}
      * @psalm-pure
     */
    private static function parseRange(string $range, int $min, int $max): array
    {
        if ($range === '*') {
            return [$min, $max];
        }
        $parts = explode('-', $range, 2);
        return [(int) $parts[0], (int) ($parts[1] ?? $parts[0])];
    }
}
