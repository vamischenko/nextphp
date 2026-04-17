<?php

declare(strict_types=1);

namespace Nextphp\Queue;

use Redis;

final class RedisQueue implements QueueInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly Redis $redis,
        private readonly string $key = 'nextphp:queue:default',
    ) {
    }

    public function push(JobInterface $job): void
    {
        $this->pushDelayed($job, 0);
    }

    public function pushDelayed(JobInterface $job, int $delaySeconds): void
    {
        $availableAt = time() + max(0, $delaySeconds);
        $this->redis->zAdd($this->key, $availableAt, serialize($job));
    }

    public function pop(): ?QueuedJob
    {
        $now = time();

        // Atomically fetch and remove the first available job via Lua script
        $script = <<<'LUA'
            local key = KEYS[1]
            local now = tonumber(ARGV[1])
            local items = redis.call('ZRANGEBYSCORE', key, '-inf', now, 'LIMIT', 0, 1)
            if #items == 0 then
                return nil
            end
            redis.call('ZREM', key, items[1])
            return items[1]
        LUA;

        $payload = $this->redis->eval($script, [$this->key, (string) $now], 1);

        if ($payload === null || $payload === false) {
            return null;
        }

        $job = unserialize((string) $payload);

        if (!$job instanceof JobInterface) {
            return null;
        }

        return new QueuedJob($job);
    }

    public function size(): int
    {
        return (int) $this->redis->zCard($this->key);
    }
}
