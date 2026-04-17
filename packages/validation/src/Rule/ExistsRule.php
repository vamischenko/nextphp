<?php

declare(strict_types=1);

namespace Nextphp\Validation\Rule;

use Nextphp\Validation\Contracts\PresenceVerifierInterface;
use Nextphp\Validation\ValidationError;
use Nextphp\Validation\ValidationRuleInterface;

final class ExistsRule implements ValidationRuleInterface
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
        if (! $this->presence->exists($this->table, $this->column, $value)) {
            return new ValidationError('validation.exists', fallback: "The selected {$field} is invalid.");
        }

        return null;
    }
}
