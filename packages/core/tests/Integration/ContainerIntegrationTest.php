<?php

declare(strict_types=1);

namespace Nextphp\Core\Tests\Integration;

use Nextphp\Core\Container\AbstractServiceProvider;
use Nextphp\Core\Container\Container;
use Nextphp\Core\Container\ContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
final class ContainerIntegrationTest extends TestCase
{
    #[Test]
    public function fullAutowiringChain(): void
    {
        $container = new Container();

        // Bind interface to concrete
        $container->bind(RepositoryInterface::class, UserRepository::class);
        $container->singleton(DatabaseConnection::class);

        // UserService depends on RepositoryInterface, which depends on DatabaseConnection
        $service = $container->make(UserService::class);

        self::assertInstanceOf(UserService::class, $service);
        self::assertInstanceOf(UserRepository::class, $service->repository);
        self::assertInstanceOf(DatabaseConnection::class, $service->repository->connection);
    }

    #[Test]
    public function singletonIsSharedAcrossAutowiringChain(): void
    {
        $container = new Container();
        $container->singleton(DatabaseConnection::class);
        $container->bind(RepositoryInterface::class, UserRepository::class);

        $service1 = $container->make(UserService::class);
        $service2 = $container->make(UserService::class);

        self::assertSame(
            $service1->repository->connection,
            $service2->repository->connection,
        );
    }

    #[Test]
    public function serviceProviderRegistersAndWiresServices(): void
    {
        $container = new Container();
        $container->addServiceProvider(new AppServiceProvider());
        $container->boot();

        $service = $container->make(UserService::class);

        self::assertInstanceOf(UserService::class, $service);
    }

    #[Test]
    public function callInjectsFromContainerAndParameters(): void
    {
        $container = new Container();
        $container->singleton(DatabaseConnection::class);

        $result = $container->call(
            fn (DatabaseConnection $db, string $query) => $query . ':' . $db->dsn,
            ['query' => 'SELECT 1'],
        );

        self::assertSame('SELECT 1:sqlite::memory:', $result);
    }
}

// --- Integration test fixtures ---

interface RepositoryInterface
{
}

final class DatabaseConnection
{
    public string $dsn = 'sqlite::memory:';
}

final class UserRepository implements RepositoryInterface
{
    public function __construct(
        public readonly DatabaseConnection $connection,
    ) {
    }
}

final class UserService
{
    public function __construct(
        public readonly RepositoryInterface $repository,
    ) {
    }
}

final class AppServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(DatabaseConnection::class);
        $container->bind(RepositoryInterface::class, UserRepository::class);
    }
}
