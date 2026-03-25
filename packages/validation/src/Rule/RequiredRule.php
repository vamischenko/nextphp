<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationError;
use Nextphp\Validation\ValidationRuleInterface;

final class RequiredRule implements ValidationRuleInterface
{
    public function validate(string $field, mixed $value, array $data): ValidationError|null
    {
        if ($value === null) {
            return new ValidationError('validation.required', fallback: "The {$field} field is required.");
        }

        if (is_string($value) && trim($value) === '') {
            return new ValidationError('validation.required', fallback: "The {$field} field is required.");
        }

        if (is_array($value) && $value === []) {
            return new ValidationError('validation.required', fallback: "The {$field} field is required.");
        }

        return null;
    }
}
