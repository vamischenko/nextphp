<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationRuleInterface;

final class RequiredRule implements ValidationRuleInterface
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if ($value === null) {
            return "The {$field} field is required.";
        }

        if (is_string($value) && trim($value) === '') {
            return "The {$field} field is required.";
        }

        if (is_array($value) && $value === []) {
            return "The {$field} field is required.";
        }

        return null;
    }
}
