<?php

declare(strict_types=1);

namespace Nextphp\Validation;

interface ValidationRuleInterface
{
    /** @param array<string, mixed> $data */
    public function validate(string $field, mixed $value, array $data): ValidationError|string|null;
}
