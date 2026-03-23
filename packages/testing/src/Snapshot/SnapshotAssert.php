<?php

declare(strict_types=1);

namespace Nextphp\Testing\Snapshot;

final class SnapshotAssert
{
    public function __construct(
        private readonly string $snapshotDir,
    ) {
    }

    public function assert(string $name, string $actual): void
    {
        if (! is_dir($this->snapshotDir) && ! mkdir($this->snapshotDir, 0777, true) && ! is_dir($this->snapshotDir)) {
            throw new \RuntimeException('Unable to create snapshot directory.');
        }

        $path = rtrim($this->snapshotDir, '/') . '/' . $name . '.snap';
        if (! is_file($path)) {
            file_put_contents($path, $actual);
            return;
        }

        $expected = (string) file_get_contents($path);
        if ($expected !== $actual) {
            throw new \RuntimeException(sprintf('Snapshot "%s" does not match.', $name));
        }
    }
}
