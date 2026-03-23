<?php

declare(strict_types=1);

namespace Nextphp\Testing;

use Nextphp\Testing\Snapshot\SnapshotAssert;

final class TestResponse
{
    /**
     * @param array<string, mixed> $json
     */
    public function __construct(
        private readonly int $status,
        private readonly string $body = '',
        private readonly array $json = [],
    ) {
    }

    public function assertStatus(int $expected): self
    {
        if ($this->status !== $expected) {
            throw new \RuntimeException(sprintf('Expected status %d, got %d.', $expected, $this->status));
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $expected
     */
    public function assertJson(array $expected): self
    {
        foreach ($expected as $key => $value) {
            if (! array_key_exists($key, $this->json) || $this->json[$key] !== $value) {
                throw new \RuntimeException(sprintf('JSON assertion failed for key "%s".', $key));
            }
        }

        return $this;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function assertBodyContains(string $needle): self
    {
        if (! str_contains($this->body, $needle)) {
            throw new \RuntimeException(sprintf('Expected response body to contain "%s".', $needle));
        }

        return $this;
    }

    public function assertMatchesSnapshot(string $name, string $snapshotDir): self
    {
        (new SnapshotAssert($snapshotDir))->assert($name, $this->body);

        return $this;
    }
}
