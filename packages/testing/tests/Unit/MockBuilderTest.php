<?php

declare(strict_types=1);

namespace Nextphp\Testing\Tests\Unit;

use Nextphp\Testing\Mock\Mock;
use Nextphp\Testing\Mock\MockBuilder;
use Nextphp\Testing\Mock\MockExpectationException;
use PHPUnit\Framework\TestCase;

// --------------- fixtures ---------------

interface GreeterInterface
{
    public function greet(string $name): string;

    public function goodbye(): void;
}

interface CalculatorInterface
{
    public function add(int $a, int $b): int;

    public function multiply(int $a, int $b): int;
}

abstract class AbstractLogger
{
    abstract public function log(string $level, string $message): void;

    public function info(string $message): void
    {
        $this->log('info', $message);
    }
}

final class FinalService
{
    public function run(): void
    {
    }
}

// --------------- tests ---------------

final class MockBuilderTest extends TestCase
{
    public function testMockInterface(): void
    {
        $mock = MockBuilder::mock(GreeterInterface::class);

        $mock->expects('greet')->andReturn('Hello, World!')->once();

        $result = $mock->greet('World');

        self::assertSame('Hello, World!', $result);
        $mock->verify();
    }

    public function testMockFacade(): void
    {
        $mock = Mock::of(CalculatorInterface::class);

        $mock->expects('add')->andReturn(5)->once();

        self::assertSame(5, $mock->add(2, 3));
        $mock->verify();
    }

    public function testExpectWithArgs(): void
    {
        $mock = MockBuilder::mock(GreeterInterface::class);

        $mock->expects('greet')->with('Alice')->andReturn('Hi Alice')->once();

        self::assertSame('Hi Alice', $mock->greet('Alice'));
        $mock->verify();
    }

    public function testWrongArgsThrowing(): void
    {
        $mock = MockBuilder::mock(GreeterInterface::class);

        $mock->expects('greet')->with('Alice')->andReturn('Hi Alice');

        $this->expectException(MockExpectationException::class);
        $mock->greet('Bob'); // wrong arg
    }

    public function testExpectNeverFails(): void
    {
        $mock = MockBuilder::mock(GreeterInterface::class);

        $mock->expects('greet')->never();

        $this->expectException(MockExpectationException::class);
        $mock->greet('Alice');
        $mock->verify();
    }

    public function testExpectTwiceFails(): void
    {
        $mock = MockBuilder::mock(GreeterInterface::class);

        $mock->expects('greet')->andReturn('Hi')->twice();

        $mock->greet('A'); // only once

        $this->expectException(MockExpectationException::class);
        $mock->verify();
    }

    public function testVerifyPassesWhenCallCountMatches(): void
    {
        $mock = MockBuilder::mock(CalculatorInterface::class);

        $mock->expects('add')->andReturn(10)->times(3);

        $mock->add(1, 2);
        $mock->add(3, 4);
        $mock->add(5, 6);

        $mock->verify(); // should not throw
        $this->addToAssertionCount(1);
    }

    public function testAndReturnUsing(): void
    {
        $mock = MockBuilder::mock(CalculatorInterface::class);

        $mock->expects('add')->andReturnUsing(static fn(int $a, int $b) => $a + $b);

        self::assertSame(7, $mock->add(3, 4));
    }

    public function testWasCalled(): void
    {
        $mock = MockBuilder::mock(GreeterInterface::class);

        self::assertFalse($mock->wasCalled('greet'));

        $mock->greet('Alice');

        self::assertTrue($mock->wasCalled('greet'));
        self::assertSame(1, $mock->callCount('greet'));
    }

    public function testCallArgs(): void
    {
        $mock = MockBuilder::mock(CalculatorInterface::class);

        $mock->add(3, 4);
        $mock->add(10, 20);

        self::assertSame([3, 4], $mock->callArgs('add', 0));
        self::assertSame([10, 20], $mock->callArgs('add', 1));
    }

    public function testVoidMethod(): void
    {
        $mock = MockBuilder::mock(GreeterInterface::class);

        $mock->expects('goodbye')->once();

        $mock->goodbye(); // returns void
        $mock->verify();
        $this->addToAssertionCount(1);
    }

    public function testMockAbstractClass(): void
    {
        $mock = MockBuilder::mock(AbstractLogger::class);

        $mock->expects('log')->with('info', 'test')->once();

        $mock->info('test'); // calls log() internally
        $mock->verify();
        $this->addToAssertionCount(1);
    }

    public function testAtLeast(): void
    {
        $mock = MockBuilder::mock(CalculatorInterface::class);

        $mock->expects('multiply')->andReturn(1)->atLeast(2);

        $mock->multiply(1, 2);
        $mock->multiply(3, 4);
        $mock->multiply(5, 6);

        $mock->verify(); // 3 >= 2, should pass
        $this->addToAssertionCount(1);
    }

    public function testAtLeastFails(): void
    {
        $mock = MockBuilder::mock(CalculatorInterface::class);

        $mock->expects('multiply')->andReturn(1)->atLeast(3);

        $mock->multiply(1, 2);

        $this->expectException(MockExpectationException::class);
        $mock->verify();
    }

    public function testFinalClassThrows(): void
    {
        $this->expectException(\LogicException::class);
        MockBuilder::mock(FinalService::class);
    }

    public function testMultipleExpectationsConsumedInOrder(): void
    {
        $mock = MockBuilder::mock(CalculatorInterface::class);

        $mock->expects('add')->andReturn(1);
        $mock->expects('add')->andReturn(2);
        $mock->expects('add')->andReturn(3);

        self::assertSame(1, $mock->add(0, 0));
        self::assertSame(2, $mock->add(0, 0));
        self::assertSame(3, $mock->add(0, 0));
    }
}
