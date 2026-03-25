<?php

declare(strict_types=1);

namespace Nextphp\Testing\Mock;

/**
 * Generates mock proxies for interfaces and abstract/concrete classes at runtime.
 *
 * For interfaces: generates a class extending MockProxy that implements the interface.
 * For classes:    generates a class extending the target class that uses MockTrait.
 *
 * Usage:
 *   $mock = MockBuilder::mock(SomeInterface::class);
 *   $mock->expects('method')->andReturn(42)->once();
 *   // ... use $mock as SomeInterface ...
 *   $mock->verify();
 */
final class MockBuilder
{
    /** @var array<string, string> target => generated class name */
    private static array $generatedClasses = [];

    /**
     * Create a mock proxy for the given class or interface.
     *
     * @template T of object
     * @param class-string<T> $classOrInterface
     * @return T&MockProxy
     */
    public static function mock(string $classOrInterface): object
    {
        $proxyClass = self::getOrCreateProxyClass($classOrInterface);

        /** @var T&MockProxy */
        return new $proxyClass();
    }

    /**
     * @param class-string<object> $target
     */
    private static function getOrCreateProxyClass(string $target): string
    {
        if (isset(self::$generatedClasses[$target])) {
            return self::$generatedClasses[$target];
        }

        $reflection = new \ReflectionClass($target);

        if ($reflection->isFinal()) {
            throw new \LogicException(sprintf('Cannot mock final class "%s".', $target));
        }

        $proxyClass  = 'NextphpMock_' . md5($target);
        $methods     = self::collectMethods($reflection);
        $methodCode  = self::generateMethods($methods);

        // Use fully-qualified class names directly in eval'd code (no addslashes — FQNs already contain backslashes)
        $proxyBase = MockProxy::class;
        $traitFqn  = MockTrait::class;

        if ($reflection->isInterface()) {
            // Extend MockProxy (has handleCall + mock state) + implement the interface
            $code = "class {$proxyClass} extends \\{$proxyBase} implements \\{$target} { {$methodCode} }";
        } else {
            // Extend the target class + use MockTrait for mock state
            $code = "class {$proxyClass} extends \\{$target} { use \\{$traitFqn}; public function __construct() {} {$methodCode} }";
        }

        eval($code); // phpcs:ignore

        self::$generatedClasses[$target] = $proxyClass;

        return $proxyClass;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     * @return \ReflectionMethod[]
     */
    private static function collectMethods(\ReflectionClass $reflection): array
    {
        $methods    = [];
        $traitMethods = array_map(
            static fn(\ReflectionMethod $m) => $m->getName(),
            (new \ReflectionClass(MockTrait::class))->getMethods(),
        );

        $candidateMethods = $reflection->isInterface()
            ? $reflection->getMethods(\ReflectionMethod::IS_PUBLIC)
            : $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($candidateMethods as $method) {
            if ($method->isConstructor() || $method->isDestructor()) {
                continue;
            }
            if ($method->isStatic()) {
                continue;
            }
            if (! $reflection->isInterface() && ! $method->isAbstract()) {
                continue;
            }
            // Skip methods that MockTrait / MockProxy already define
            if (in_array($method->getName(), $traitMethods, true)) {
                continue;
            }
            if ($reflection->isInterface() === false && method_exists(MockProxy::class, $method->getName())) {
                continue;
            }
            $methods[] = $method;
        }

        return $methods;
    }

    /**
     * @param \ReflectionMethod[] $methods
     */
    private static function generateMethods(array $methods): string
    {
        $code = '';
        foreach ($methods as $method) {
            $code .= self::generateMethod($method);
        }

        return $code;
    }

    private static function generateMethod(\ReflectionMethod $method): string
    {
        $name       = $method->getName();
        $params     = self::generateParams($method);
        $passArgs   = self::generatePassArgs($method);
        $returnType = $method->getReturnType();
        $returnStr  = $returnType !== null ? ': ' . self::renderType($returnType) : '';

        // void methods: call handleCall but don't return its value
        if ($returnType instanceof \ReflectionNamedType && $returnType->getName() === 'void') {
            return sprintf(
                'public function %s(%s): void { $this->handleCall(%s, [%s], \'void\'); }',
                $name,
                $params,
                var_export($name, true),
                $passArgs,
            );
        }

        // never methods cannot be meaningfully mocked
        if ($returnType instanceof \ReflectionNamedType && $returnType->getName() === 'never') {
            return sprintf(
                'public function %s(%s): never { throw new \\RuntimeException("Mocked never-returning method %s was called"); }',
                $name,
                $params,
                $name,
            );
        }

        // Pass the base return type name so handleCall can return a type-safe default
        $baseTypeName = ($returnType instanceof \ReflectionNamedType) ? var_export($returnType->getName(), true) : 'null';

        return sprintf(
            'public function %s(%s)%s { return $this->handleCall(%s, [%s], %s); }',
            $name,
            $params,
            $returnStr,
            var_export($name, true),
            $passArgs,
            $baseTypeName,
        );
    }

    private static function generateParams(\ReflectionMethod $method): string
    {
        $parts = [];
        foreach ($method->getParameters() as $param) {
            $part = '';

            $type = $param->getType();
            if ($type !== null) {
                $part .= self::renderType($type) . ' ';
            }

            if ($param->isVariadic()) {
                $part .= '...';
            }

            $part .= '$' . $param->getName();

            if (!$param->isVariadic() && $param->isOptional() && $param->isDefaultValueAvailable()) {
                try {
                    $default = $param->getDefaultValue();
                    $part   .= ' = ' . var_export($default, true);
                } catch (\ReflectionException) {
                    // inherited constant — skip default
                }
            }

            $parts[] = $part;
        }

        return implode(', ', $parts);
    }

    private static function generatePassArgs(\ReflectionMethod $method): string
    {
        $parts = [];
        foreach ($method->getParameters() as $param) {
            if ($param->isVariadic()) {
                $parts[] = '...$' . $param->getName();
            } else {
                $parts[] = '$' . $param->getName();
            }
        }

        return implode(', ', $parts);
    }

    private static function renderType(\ReflectionType $type): string
    {
        if ($type instanceof \ReflectionNamedType) {
            $name = $type->getName();
            if (!$type->isBuiltin() && !str_starts_with($name, '\\')) {
                $name = '\\' . $name;
            }
            if ($type->allowsNull() && $name !== 'null' && $name !== 'mixed') {
                return '?' . $name;
            }

            return $name;
        }

        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map(
                static fn(\ReflectionType $t) => self::renderType($t),
                $type->getTypes(),
            ));
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return implode('&', array_map(
                static fn(\ReflectionType $t) => self::renderType($t),
                $type->getTypes(),
            ));
        }

        return 'mixed';
    }
}
