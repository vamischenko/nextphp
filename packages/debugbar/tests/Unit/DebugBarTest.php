<?php

declare(strict_types=1);

namespace Nextphp\Debugbar\Tests\Unit;

use Nextphp\Debugbar\Collector\MemoryCollector;
use Nextphp\Debugbar\Collector\QueryCollector;
use Nextphp\Debugbar\Collector\TimelineCollector;
use Nextphp\Debugbar\DebugBar;
use Nextphp\Debugbar\Renderer\HtmlRenderer;
use Nextphp\Debugbar\Stream\StringStream;
use PHPUnit\Framework\TestCase;

final class DebugBarTest extends TestCase
{
    public function testCollectAll(): void
    {
        $bar = new DebugBar();
        $bar->addCollector(new MemoryCollector());

        $data = $bar->collectAll();

        self::assertArrayHasKey('memory', $data);
        self::assertArrayHasKey('current_mb', $data['memory']);
        self::assertArrayHasKey('peak_mb', $data['memory']);
    }

    public function testQueryCollector(): void
    {
        $collector = new QueryCollector();
        $collector->addQuery('SELECT 1', [], 1.5);
        $collector->addQuery('SELECT 2', ['id' => 1], 2.0);

        $data = $collector->collect();

        self::assertSame(2, $data['count']);
        self::assertSame(3.5, $data['total_ms']);
        self::assertCount(2, $data['queries']);
        self::assertSame('SELECT 1', $data['queries'][0]['sql']);
    }

    public function testTimelineCollector(): void
    {
        $collector = new TimelineCollector();
        $collector->start('boot', 'Application Boot');
        usleep(1000); // 1 ms
        $collector->stop('boot');

        $data = $collector->collect();

        self::assertArrayHasKey('total_ms', $data);
        self::assertCount(1, $data['entries']);
        self::assertSame('Application Boot', $data['entries'][0]['label']);
        self::assertGreaterThanOrEqual(0.5, $data['entries'][0]['duration_ms']);
    }

    public function testTimelineUnstoppedMeasure(): void
    {
        $collector = new TimelineCollector();
        $collector->start('unstopped');
        // Don't stop — should still return an entry with current time as end

        $data = $collector->collect();

        self::assertCount(1, $data['entries']);
    }

    public function testIsEnabled(): void
    {
        $bar = new DebugBar(true);
        self::assertTrue($bar->isEnabled());

        $barDisabled = new DebugBar(false);
        self::assertFalse($barDisabled->isEnabled());
    }

    public function testGetCollector(): void
    {
        $bar       = new DebugBar();
        $collector = new MemoryCollector();
        $bar->addCollector($collector);

        self::assertSame($collector, $bar->getCollector('memory'));
        self::assertNull($bar->getCollector('nonexistent'));
    }

    public function testHtmlRendererProducesHtml(): void
    {
        $bar = new DebugBar();
        $bar->addCollector(new MemoryCollector());
        $bar->addCollector(new QueryCollector());

        $renderer = new HtmlRenderer();
        $html     = $renderer->render($bar);

        self::assertStringContainsString('nphp-debugbar', $html);
        self::assertStringContainsString('Memory', $html);
        self::assertStringContainsString('Queries', $html);
    }

    public function testHtmlRendererContainsQueryCount(): void
    {
        $bar       = new DebugBar();
        $collector = new QueryCollector();
        $collector->addQuery('SELECT * FROM users');
        $collector->addQuery('SELECT * FROM posts');
        $bar->addCollector($collector);

        $html = (new HtmlRenderer())->render($bar);

        self::assertStringContainsString('2', $html);
    }

    public function testStringStream(): void
    {
        $stream = new StringStream('hello world');

        self::assertSame(11, $stream->getSize());
        self::assertFalse($stream->eof());
        self::assertSame('hello', $stream->read(5));
        self::assertSame(' world', $stream->getContents());
        self::assertTrue($stream->eof());

        $stream->rewind();
        self::assertSame('hello world', (string) $stream);
    }

    public function testStringStreamWrite(): void
    {
        $stream = new StringStream('hello');
        $stream->seek(5);
        $stream->write(' world');

        $stream->rewind();
        self::assertSame('hello world', $stream->getContents());
    }

    public function testStringStreamSeekEnd(): void
    {
        $stream = new StringStream('hello');
        $stream->seek(-3, SEEK_END);

        self::assertSame('llo', $stream->getContents());
    }
}
