<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationError;
use Nextphp\Validation\ValidationRuleInterface;

final class IntegerRule implements ValidationRuleInterface
{
    /**
      * @psalm-pure
     */
    public function validate(string $field, mixed $value, array $data): ValidationError|null
    {
        if ($value === null) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return new ValidationError('validation.integer', fallback: "The {$field} field must be an integer.");
        }

        return null;
    }
}
