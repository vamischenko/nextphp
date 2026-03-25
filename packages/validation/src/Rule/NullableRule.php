<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationRuleInterface;

final class NullableRule implements ValidationRuleInterface
{
    public function validate(string $field, mixed $value, array $data): string|null
    {
        // Nullable itself never fails — it signals that null is acceptable.
        // The Validator uses this as a marker to skip further rules when value is null.
        return null;
    }
}
