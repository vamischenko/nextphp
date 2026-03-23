<?php

declare(strict_types=1);

namespace Nextphp\Testing\Tests\Unit;

use Nextphp\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(\Nextphp\Testing\TestResponse::class)]
final class TestResponseTest extends TestCase
{
    #[Test]
    public function assertStatusAndJsonHelpers(): void
    {
        $this->response(200, '{"ok":true}', ['ok' => true])
            ->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertBodyContains('ok');

        self::assertTrue(true);
    }
}
