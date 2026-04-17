<?php

declare(strict_types=1);

namespace Nextphp\Validation\Presence;

use Nextphp\Validation\Contracts\PresenceVerifierInterface;

final class InMemoryPresenceVerifier implements PresenceVerifierInterface
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $data = [];

    /**
     * @param array<string, array<int, array<string, mixed>>> $data
       * @psalm-mutation-free
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
      * @psalm-mutation-free
     */
    public function exists(string $table, string $column, mixed $value): bool
    {
        foreach ($this->data[$table] ?? [] as $row) {
            if (($row[$column] ?? null) === $value) {
                return true;
            }
        }

        return false;
    }

    /**
      * @psalm-mutation-free
     */
    public function unique(string $table, string $column, mixed $value): bool
    {
        return ! $this->exists($table, $column, $value);
    }
}
