<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\ValidationError;
use Nextphp\Validation\ValidationRuleInterface;

final class MaxRule implements ValidationRuleInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly int $max,
    ) {
    }

    /**
      * @psalm-mutation-free
     */
    public function validate(string $field, mixed $value, array $data): ValidationError|null
    {
        if (is_string($value) && mb_strlen($value) > $this->max) {
            return new ValidationError(
                'validation.max.string',
                ['max' => $this->max],
                "The {$field} must not exceed {$this->max} characters.",
            );
        }

        if (is_array($value) && count($value) > $this->max) {
            return new ValidationError(
                'validation.max.array',
                ['max' => $this->max],
                "The {$field} must not contain more than {$this->max} items.",
            );
        }

        if (is_numeric($value) && (float) $value > $this->max) {
            return new ValidationError(
                'validation.max.numeric',
                ['max' => $this->max],
                "The {$field} must not exceed {$this->max}.",
            );
        }

        return null;
    }
}
