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

    /**
      * @psalm-mutation-free
     */
    public function __construct(private readonly string $method)
    {
    }

    /**
      * @psalm-external-mutation-free
     */
    public function andReturn(mixed $value): static
    {
        $this->returnValue    = $value;
        $this->returnCallback = null;

        return $this;
    }

    /**
     * @param callable(): mixed $callback
       * @psalm-external-mutation-free
     */
    public function andReturnUsing(callable $callback): static
    {
        $this->returnCallback = $callback;

        return $this;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function andReturnNull(): static
    {
        return $this->andReturn(null);
    }

    /** @param mixed ...$args */
    /**
      * @psalm-external-mutation-free
     */
    public function with(mixed ...$args): static
    {
        $this->expectedArgs = $args;

        return $this;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function once(): static
    {
        $this->expectedCallCount = 1;

        return $this;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function twice(): static
    {
        $this->expectedCallCount = 2;

        return $this;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function times(int $n): static
    {
        $this->expectedCallCount = $n;

        return $this;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function never(): static
    {
        $this->expectedCallCount = 0;

        return $this;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function atLeast(int $n): static
    {
        // Store as negative sentinel: -n means "at least n"
        $this->expectedCallCount = -$n;

        return $this;
    }

    /**
      * @psalm-external-mutation-free
     */
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
       * @psalm-mutation-free
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
