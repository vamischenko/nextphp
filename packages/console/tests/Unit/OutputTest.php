<?php

declare(strict_types=1);

namespace Nextphp\Console\Tests\Unit;

use Nextphp\Console\Output;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Output::class)]
final class OutputTest extends TestCase
{
    private function makeOutput(): Output
    {
        // colours=false so buffer contains plain text without ANSI escapes
        return new Output(false);
    }

    #[Test]
    public function lineAppendsToBuffer(): void
    {
        $out = $this->makeOutput();
        $out->line('hello');
        self::assertStringContainsString('hello', implode('', $out->buffer()));
    }

    #[Test]
    public function tableRendersAlignedColumns(): void
    {
        $out = $this->makeOutput();
        $out->table(
            ['Name', 'Status'],
            [
                ['Alice', 'active'],
                ['Bob', 'inactive'],
            ],
        );
        $text = implode('', $out->buffer());
        self::assertStringContainsString('Name', $text);
        self::assertStringContainsString('Status', $text);
        self::assertStringContainsString('Alice', $text);
        self::assertStringContainsString('inactive', $text);
        // separator line
        self::assertStringContainsString('+', $text);
    }

    #[Test]
    public function progressTracksPercentage(): void
    {
        $out = $this->makeOutput();
        $out->progress(5, 10);
        $text = implode('', $out->buffer());
        self::assertStringContainsString('50%', $text);
    }

    #[Test]
    public function progressDoesNothingForZeroTotal(): void
    {
        $out = $this->makeOutput();
        $out->progress(0, 0);
        self::assertEmpty($out->buffer());
    }

    #[Test]
    public function clearBufferEmptiesBuffer(): void
    {
        $out = $this->makeOutput();
        $out->line('test');
        $out->clearBuffer();
        self::assertEmpty($out->buffer());
    }

    #[Test]
    public function legacyTableCallWithNoHeaders(): void
    {
        $out = $this->makeOutput();
        // Legacy: table([['a', 'b'], ['c', 'd']])
        $out->table([['a', 'b'], ['c', 'd']]); // @phpstan-ignore-line
        $text = implode('', $out->buffer());
        self::assertStringContainsString('a', $text);
        self::assertStringContainsString('c', $text);
    }
}
