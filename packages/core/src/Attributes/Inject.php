<?php

declare(strict_types=1);

namespace Nextphp\Core\Attributes;

use Attribute;

/**
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class Inject
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly string $abstract,
    ) {
    }
}
