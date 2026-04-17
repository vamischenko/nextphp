<?php

declare(strict_types=1);

namespace Nextphp\Testing\Mock;

/**
 * Base class for generated mock proxies.
 *
 * The MockBuilder creates an anonymous class that extends the target class
 * (or implements the target interface) AND extends this class.
 * All declared methods are overridden to delegate to handleCall().
 */
abstract class MockProxy
{
    /** @var array<string, ExpectationBuilder[]> */
    private array $expectations = [];

    /** @var array<string, mixed[][]> recorded calls: method => [[arg, ...], ...] */
    private array $calls = [];

    /**
     * Register an expectation for a method call.
     * Multiple expectations for the same method are matched in order.
       * @psalm-external-mutation-free
     */
    public function expects(string $method): ExpectationBuilder
    {
        $expectation = new ExpectationBuilder($method);
        $this->expectations[$method][] = $expectation;

        return $expectation;
    }

    /**
     * Returns true if the method was called at least once.
       * @psalm-mutation-free
     */
    public function wasCalled(string $method): bool
    {
        return isset($this->calls[$method]) && count($this->calls[$method]) > 0;
    }

    /**
     * Returns the number of times the method was called.
       * @psalm-mutation-free
     */
    public function callCount(string $method): int
    {
        return isset($this->calls[$method]) ? count($this->calls[$method]) : 0;
    }

    /**
     * Returns the arguments of the n-th call (0-indexed).
     *
     * @return array<mixed>
       * @psalm-mutation-free
     */
    public function callArgs(string $method, int $index = 0): array
    {
        return $this->calls[$method][$index] ?? [];
    }

    /**
     * Verify all registered expectations. Call this at the end of the test.
     */
    public function verify(): void
    {
        foreach ($this->expectations as $perMethod) {
            foreach ($perMethod as $expectation) {
                $expectation->verify();
            }
        }
    }

    /**
     * Called by every overridden method in the generated proxy.
     *
     * @param array<mixed> $args
     * @param string|null  $returnTypeName PHP return type name for default value generation
     */
    protected function handleCall(string $method, array $args, ?string $returnTypeName = null): mixed
    {
        $this->calls[$method][] = $args;

        if (isset($this->expectations[$method]) && count($this->expectations[$method]) > 0) {
            // Use first matching expectation (round-robin)
            $expectation = $this->expectations[$method][0];

            // If multiple expectations exist, pop the first after first use
            if (count($this->expectations[$method]) > 1) {
                array_shift($this->expectations[$method]);
            }

            return $expectation->recordCall($args);
        }

        // No expectation: return a type-safe default
        return self::defaultForType($returnTypeName);
    }

    /**
     * @psalm-pure
     */
    private static function defaultForType(?string $type): mixed
    {
        return match ($type) {
            'int'    => 0,
            'float'  => 0.0,
            'bool'   => false,
            'string' => '',
            'array'  => [],
            default  => null,
        };
    }
}
