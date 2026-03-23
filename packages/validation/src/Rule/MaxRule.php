<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationRuleInterface;

final class MaxRule implements ValidationRuleInterface
{
    public function __construct(
        private readonly int $max,
    ) {
    }

    public function validate(string $field, mixed $value, array $data): ?string
    {
        if (is_string($value) && mb_strlen($value) > $this->max) {
            return "The {$field} must not exceed {$this->max} characters.";
        }

        if (is_array($value) && count($value) > $this->max) {
            return "The {$field} must not contain more than {$this->max} items.";
        }

        if (is_numeric($value) && (float) $value > $this->max) {
            return "The {$field} must not exceed {$this->max}.";
        }

        return null;
    }
}
