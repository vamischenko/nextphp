<?php

declare(strict_types=1);

namespace Nextphp\Validation;

/**
 * @psalm-mutable
 */
interface ValidationRuleInterface
{
    /**
     * @param array<string, mixed> $data
     * @psalm-impure
     */
    public function validate(string $field, mixed $value, array $data): ValidationError|string|null;
}
