<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationRuleInterface;

final class EmailRule implements ValidationRuleInterface
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return "The {$field} must be a valid email address.";
        }

        return null;
    }
}
