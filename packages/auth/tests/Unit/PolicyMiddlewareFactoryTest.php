<?php

declare(strict_types=1);

namespace Nextphp\Auth\Tests\Unit;

use Nextphp\Auth\PolicyMiddlewareFactory;
use Nextphp\Auth\PolicyRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PolicyMiddlewareFactory::class)]
final class PolicyMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function createsCanMiddlewareFromAlias(): void
    {
        $registry = new PolicyRegistry();
        $factory = new PolicyMiddlewareFactory($registry);
        $middleware = $factory('can:view-dashboard');

        self::assertInstanceOf(\Nextphp\Auth\CanMiddleware::class, $middleware);
    }
}
