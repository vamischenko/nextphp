<?php

declare(strict_types=1);

namespace Nextphp\Testing\Internal;

use Nextphp\Testing\Database\DatabaseTransactions;
use Nextphp\Testing\Database\RefreshDatabase;

/**
 * Ensures static analysers see DatabaseTransactions and RefreshDatabase as used.
 * Real runtime usage happens in user test-case classes.
 *
 * @internal
 */
final class DatabaseTraitsUsage
{
    use DatabaseTransactions;
    use RefreshDatabase;
}
