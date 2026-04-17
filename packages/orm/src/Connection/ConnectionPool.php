<?php

declare(strict_types=1);

namespace Nextphp\Orm\Connection;

use Nextphp\Orm\Exception\OrmException;

/**
 * @psalm-immutable
 */
final class ConnectionPool
{
    /** @var array<string, ConnectionInterface> */
    private array $connections = [];

    private string $default = 'default';

    public function add(string $name, ConnectionInterface $connection): void
    {
        $this->connections[$name] = $connection;
    }

    public function setDefault(string $name): void
    {
        $this->default = $name;
    }

    public function get(?string $name = null): ConnectionInterface
    {
        $name ??= $this->default;

        if (! isset($this->connections[$name])) {
            throw new OrmException(sprintf('Connection "%s" not found in pool.', $name));
        }

        return $this->connections[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->connections[$name]);
    }
}
