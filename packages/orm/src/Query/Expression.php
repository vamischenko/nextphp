<?php

declare(strict_types=1);

namespace Nextphp\Orm\Query;

/**
 * Wraps a raw SQL expression that should not be escaped.
 */
final readonly class Expression
{
    public function __construct(
        public string $value,
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
