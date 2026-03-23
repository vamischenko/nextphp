<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationRuleInterface;

final class ConfirmedRule implements ValidationRuleInterface
{
    public function validate(string $field, mixed $value, array $data): ?string
    {
        $confirmationKey = $field . '_confirmation';

        if (!array_key_exists($confirmationKey, $data) || $data[$confirmationKey] !== $value) {
            return "The {$field} field confirmation does not match.";
        }

        return null;
    }
}
