<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\Contracts\PresenceVerifierInterface;
use Nextphp\Validation\ValidationError;
use Nextphp\Validation\ValidationRuleInterface;

final class UniqueRule implements ValidationRuleInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly PresenceVerifierInterface $presence,
        private readonly string $table,
        private readonly string $column,
    ) {
    }

    public function validate(string $field, mixed $value, array $data): ValidationError|null
    {
        if (! $this->presence->unique($this->table, $this->column, $value)) {
            return new ValidationError('validation.unique', fallback: "The {$field} has already been taken.");
        }

        return null;
    }
}
