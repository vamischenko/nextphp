<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection;

/**
 * Backward-compatible alias for PdoConnection.
 *
 * All existing code that depends on Connection::class continues to work.
 * New code should type-hint against ConnectionInterface or SqlConnectionInterface.
 */
class Connection extends PdoConnection
{
}
