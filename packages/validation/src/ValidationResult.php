<?php

declare(strict_types=1);

namespace Nextphp\Validation;

/**
  * @psalm-immutable
 */
final class ValidationResult
{
    /**
     * @param array<string, string[]> $errors
       * @psalm-mutation-free
     */
    public function __construct(
        private readonly array $errors,
    ) {
    }

    /**
      * @psalm-mutation-free
     */
    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
      * @psalm-mutation-free
     */
    public function fails(): bool
    {
        return ! $this->passes();
    }

    /**
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return string[]
       * @psalm-mutation-free
     */
    public function errorsFor(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
}
