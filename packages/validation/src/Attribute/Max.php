<?php

declare(strict_types=1);

namespace Nextphp\Validation\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Max
{
    public function __construct(public readonly int $value)
    {
    }
}
