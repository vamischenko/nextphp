<?php

declare(strict_types=1);

namespace Nextphp\Testing\Mock;

/**
 * Fluent builder for a single method expectation on a mock object.
 *
 * Usage:
 *   $mock->expects('send')->with('hello')->andReturn(true)->once();
 */
final class ExpectationBuilder
{
    private mixed $returnValue = null;

    /** @var callable|null */
    private mixed $returnCallback = null;

    private ?int $expectedCallCount = null;

    /** @var array<mixed>|null */
    private ?array $expectedArgs = null;

    private int $actualCallCount = 0;

    public function __construct(private readonly string $method)
    {
    }

    public function andReturn(mixed $value): static
    {
        $this->returnValue    = $value;
        $this->returnCallback = null;

        return $this;
    }

    /**
     * @param callable(): mixed $callback
     */
    public function andReturnUsing(callable $callback): static
    {
        $this->returnCallback = $callback;

        return $this;
    }

    public function andReturnNull(): static
    {
        return $this->andReturn(null);
    }

    /** @param mixed ...$args */
    public function with(mixed ...$args): static
    {
        $this->expectedArgs = $args;

        return $this;
    }

    public function once(): static
    {
        $this->expectedCallCount = 1;

        return $this;
    }

    public function twice(): static
    {
        $this->expectedCallCount = 2;

        return $this;
    }

    public function times(int $n): static
    {
        $this->expectedCallCount = $n;

        return $this;
    }

    public function never(): static
    {
        $this->expectedCallCount = 0;

        return $this;
    }

    public function atLeast(int $n): static
    {
        // Store as negative sentinel: -n means "at least n"
        $this->expectedCallCount = -$n;

        return $this;
    }

    public function zeroOrMoreTimes(): static
    {
        $this->expectedCallCount = null;

        return $this;
    }

    /**
     * Called by the generated proxy when the method is invoked.
     *
     * @param array<mixed> $args
     */
    public function recordCall(array $args): mixed
    {
        $this->actualCallCount++;

        if ($this->expectedCallCount === 0) {
            throw new MockExpectationException(sprintf(
                'Method %s() was not expected to be called.',
                $this->method,
            ));
        }

        if ($this->expectedArgs !== null) {
            if ($args !== $this->expectedArgs) {
                throw new MockExpectationException(sprintf(
                    'Method %s() called with unexpected arguments. Expected %s, got %s.',
                    $this->method,
                    json_encode($this->expectedArgs),
                    json_encode($args),
                ));
            }
        }

        if ($this->returnCallback !== null) {
            return ($this->returnCallback)(...$args);
        }

        return $this->returnValue;
    }

    /**
     * Verify call-count expectations. Called in tearDown / verify().
     */
    public function verify(): void
    {
        if ($this->expectedCallCount === null) {
            return; // zeroOrMoreTimes
        }

        if ($this->expectedCallCount >= 0) {
            if ($this->actualCallCount !== $this->expectedCallCount) {
                throw new MockExpectationException(sprintf(
                    'Method %s() expected to be called %d time(s), but was called %d time(s).',
                    $this->method,
                    $this->expectedCallCount,
                    $this->actualCallCount,
                ));
            }
        } else {
            // atLeast(-n)
            $min = -$this->expectedCallCount;
            if ($this->actualCallCount < $min) {
                throw new MockExpectationException(sprintf(
                    'Method %s() expected to be called at least %d time(s), but was called %d time(s).',
                    $this->method,
                    $min,
                    $this->actualCallCount,
                ));
            }
        }
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
