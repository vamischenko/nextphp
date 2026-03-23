<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationRuleInterface;

final class MinRule implements ValidationRuleInterface
{
    public function __construct(
        private readonly int $min,
    ) {
    }

    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (is_string($value) && mb_strlen($value) < $this->min) {
            return "The {$field} must be at least {$this->min} characters.";
        }

        if (is_array($value) && count($value) < $this->min) {
            return "The {$field} must contain at least {$this->min} items.";
        }

        if (is_numeric($value) && (float) $value < $this->min) {
            return "The {$field} must be at least {$this->min}.";
        }

        return null;
    }
}
