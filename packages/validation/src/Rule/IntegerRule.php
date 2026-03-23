<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationRuleInterface;

final class IntegerRule implements ValidationRuleInterface
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if ($value === null) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return "The {$field} field must be an integer.";
        }

        return null;
    }
}
