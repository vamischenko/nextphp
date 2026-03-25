<?php

declare(strict_types=1);

namespace Nextphp\Testing\Mock;

/**
 * Provides all mock state and API for class-based proxies.
 * Used when the proxy must extend the target class (instead of MockProxy).
 */
trait MockTrait
{
    /** @var array<string, ExpectationBuilder[]> */
    private array $__expectations = [];

    /** @var array<string, mixed[][]> */
    private array $__calls = [];

    public function expects(string $method): ExpectationBuilder
    {
        $expectation = new ExpectationBuilder($method);
        $this->__expectations[$method][] = $expectation;

        return $expectation;
    }

    public function wasCalled(string $method): bool
    {
        return isset($this->__calls[$method]) && count($this->__calls[$method]) > 0;
    }

    public function callCount(string $method): int
    {
        return isset($this->__calls[$method]) ? count($this->__calls[$method]) : 0;
    }

    /**
     * @return array<mixed>
     */
    public function callArgs(string $method, int $index = 0): array
    {
        return $this->__calls[$method][$index] ?? [];
    }

    public function verify(): void
    {
        foreach ($this->__expectations as $perMethod) {
            foreach ($perMethod as $expectation) {
                $expectation->verify();
            }
        }
    }

    /**
     * @param array<mixed> $args
     */
    protected function handleCall(string $method, array $args, ?string $returnTypeName = null): mixed
    {
        $this->__calls[$method][] = $args;

        if (isset($this->__expectations[$method]) && count($this->__expectations[$method]) > 0) {
            $expectation = $this->__expectations[$method][0];

            if (count($this->__expectations[$method]) > 1) {
                array_shift($this->__expectations[$method]);
            }

            return $expectation->recordCall($args);
        }

        return match ($returnTypeName) {
            'int'    => 0,
            'float'  => 0.0,
            'bool'   => false,
            'string' => '',
            'array'  => [],
            default  => null,
        };
    }
}
