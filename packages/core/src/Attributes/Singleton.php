<?php

declare(strict_types=1);

namespace Nextphp\Core\Attributes;

use Attribute;

/**
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Singleton
{
}
