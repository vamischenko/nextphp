<?php

declare(strict_types=1);

namespace Nextphp\Validation;

final readonly class ValidationError
{
    /**
     * @param array<string, scalar> $params
     */
    public function __construct(
        public string $key,
        public array $params = [],
        public ?string $fallback = null,
    ) {
    }
}

