<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationError;
use Nextphp\Validation\ValidationRuleInterface;

final class BooleanRule implements ValidationRuleInterface
{
    private const ACCEPTABLE = [true, false, 0, 1, '0', '1', 'true', 'false'];

    /**
      * @psalm-pure
     */
    public function validate(string $field, mixed $value, array $data): ValidationError|null
    {
        if ($value === null) {
            return null;
        }

        if (!in_array($value, self::ACCEPTABLE, strict: true)) {
            return new ValidationError('validation.boolean', fallback: "The {$field} field must be a boolean value.");
        }

        return null;
    }
}
