<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationRuleInterface;

/**
 * Wraps an arbitrary callable as a validation rule.
 *
 * The callable receives ($field, $value, $data) and must return
 * a string error message or null on success.
 *
 * @example
 *   $rules = [
 *       'age' => [static fn(string $f, mixed $v): ?string => $v >= 18 ? null : "$f must be 18+"],
 *   ];
 */
final class ClosureRule implements ValidationRuleInterface
{
    /**
     * @param callable(string, mixed, array<string, mixed>): ?string $callback
       * @psalm-mutation-free
     */
    public function __construct(private readonly mixed $callback)
    {
    }

    /** @param array<string, mixed> $data */
    public function validate(string $field, mixed $value, array $data): string|null
    {
        return ($this->callback)($field, $value, $data);
    }
}
