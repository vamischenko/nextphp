<?php

declare(strict_types=1);

namespace Nextphp\Queue;

/**
 * Marker interface for jobs that declare their own retry policy.
 * When implemented, Worker will use these values instead of its own defaults.
 */
interface RetryableJobInterface extends JobInterface
{
    /**
     * Maximum number of attempts before the job is sent to the dead-letter queue.
     */
    public function maxTries(): int;

    /**
     * Number of seconds to wait before the next retry attempt.
     * Used as the base delay; the backoff strategy may multiply it.
     */
    public function retryAfterSeconds(): int;
}
