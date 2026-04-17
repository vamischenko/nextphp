<?php

declare(strict_types=1);

namespace Nextphp\Validation\Attribute;

use Attribute;

/**
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Max
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(public readonly int $value)
    {
    }
}
