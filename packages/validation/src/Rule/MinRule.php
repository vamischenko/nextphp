<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationError;
use Nextphp\Validation\ValidationRuleInterface;

final class MinRule implements ValidationRuleInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly int $min,
    ) {
    }

    /**
      * @psalm-mutation-free
     */
    public function validate(string $field, mixed $value, array $data): ValidationError|null
    {
        if (is_string($value) && mb_strlen($value) < $this->min) {
            return new ValidationError(
                'validation.min.string',
                ['min' => $this->min],
                "The {$field} must be at least {$this->min} characters.",
            );
        }

        if (is_array($value) && count($value) < $this->min) {
            return new ValidationError(
                'validation.min.array',
                ['min' => $this->min],
                "The {$field} must contain at least {$this->min} items.",
            );
        }

        if (is_numeric($value) && (float) $value < $this->min) {
            return new ValidationError(
                'validation.min.numeric',
                ['min' => $this->min],
                "The {$field} must be at least {$this->min}.",
            );
        }

        return null;
    }
}
