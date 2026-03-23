<?php

declare(strict_types=1);

namespace Nextphp\Validation;

interface ValidationRuleInterface
{
    public function validate(string $field, mixed $value, array $data): ?string;
}
