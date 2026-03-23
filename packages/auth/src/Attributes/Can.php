<?php

declare(strict_types=1);

namespace Nextphp\Auth\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Can
{
    public function __construct(
        public readonly string $ability,
    ) {
    }
}
