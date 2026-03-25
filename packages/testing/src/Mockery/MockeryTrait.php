<?php

declare(strict_types=1);

namespace Nextphp\Testing\Mockery;

trait MockeryTrait
{
    protected function tearDown(): void
    {
        if (class_exists('Mockery')) {
            /** @var callable(): void $close */
            $close = 'Mockery::close';
            /** @psalm-suppress UndefinedClass */
            $close();
        }

        parent::tearDown();
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return object
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    protected function mockery(string $class): object
    {
        /** @psalm-suppress UndefinedClass */
        /** @var callable(string): object $mock */
        $mock = 'Mockery::mock';
        return $mock($class);
    }
}

