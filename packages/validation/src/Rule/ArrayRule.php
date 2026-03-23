<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationRuleInterface;

final class ArrayRule implements ValidationRuleInterface
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            return "The {$field} field must be an array.";
        }

        return null;
    }
}
