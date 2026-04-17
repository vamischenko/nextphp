<?php

declare(strict_types=1);

namespace Nextphp\Validation\Attribute;

use Attribute;

/**
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Required
{
}
