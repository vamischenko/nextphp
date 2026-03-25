<?php

declare(strict_types=1);

namespace Nextphp\Testing\Internal;

use Nextphp\Testing\Mock\MockTrait;

/**
 * Ensures static analyzers see MockTrait as used.
 * Real runtime usage happens in generated proxies.
 *
 * @psalm-suppress UnusedClass
 */
final class MockTraitUsage
{
    use MockTrait;
}

