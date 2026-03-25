<?php

declare(strict_types=1);

namespace Nextphp\View\Tests\Unit;

use Nextphp\View\Compiler;
use Nextphp\View\ComponentRegistry;
use Nextphp\View\Exception\ViewException;
use Nextphp\View\ViewEngine;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViewEngine::class)]
#[CoversClass(Compiler::class)]
#[CoversClass(ComponentRegistry::class)]
final class ViewEngineTest extends TestCase
{
    private string $base;
    private string $compiled;

    protected function setUp(): void
    {
        $this->base     = sys_get_temp_dir() . '/nextphp_view_tests_' . uniqid();
        $this->compiled = $this->base . '/compiled';
        @mkdir($this->base, 0777, true);
        @mkdir($this->base . '/parts', 0777, true);
        @mkdir($this->base . '/components', 0777, true);
        @mkdir($this->base . '/layouts', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->base);
    }

    // -------------------------------------------------------------------------
    // Echo
    // -------------------------------------------------------------------------

    #[Test]
    public function escapedEcho(): void
    {
        $this->writeView('test', '<p>{{ $name }}</p>');
        $result = $this->engine()->render('test', ['name' => '<b>World</b>']);
        self::assertSame('<p>&lt;b&gt;World&lt;/b&gt;</p>', $result);
    }

    #[Test]
    public function rawEcho(): void
    {
        $this->writeView('test', '<p>{!! $html !!}</p>');
        $result = $this->engine()->render('test', ['html' => '<b>bold</b>']);
        self::assertSame('<p><b>bold</b></p>', $result);
    }

    // -------------------------------------------------------------------------
    // Control structures
    // -------------------------------------------------------------------------

    #[Test]
    public function ifDirective(): void
    {
        $this->writeView('test', '@if($show)yes@endif');
        self::assertSame('yes', $this->engine()->render('test', ['show' => true]));
        self::assertSame('', $this->engine()->render('test', ['show' => false]));
    }

    #[Test]
    public function ifElseDirective(): void
    {
        $this->writeView('test', '@if($v === 1)one@elseif($v === 2)two@elsemany@endif');
        self::assertSame('one', $this->engine()->render('test', ['v' => 1]));
        self::assertSame('two', $this->engine()->render('test', ['v' => 2]));
        self::assertSame('many', $this->engine()->render('test', ['v' => 99]));
    }

    #[Test]
    public function unlessDirective(): void
    {
        $this->writeView('test', '@unless($hidden)visible@endunless');
        self::assertSame('visible', $this->engine()->render('test', ['hidden' => false]));
        self::assertSame('', $this->engine()->render('test', ['hidden' => true]));
    }

    #[Test]
    public function foreachDirective(): void
    {
        $this->writeView('test', '@foreach($items as $item){{ $item }},@endforeach');
        $result = $this->engine()->render('test', ['items' => ['a', 'b', 'c']]);
        self::assertSame('a,b,c,', $result);
    }

    #[Test]
    public function forelseDirective(): void
    {
        $this->writeView('test', '@forelse($items as $i){{ $i }}@emptyNone@endforelse');
        self::assertSame('ab', $this->engine()->render('test', ['items' => ['a', 'b']]));
        self::assertSame('None', $this->engine()->render('test', ['items' => []]));
    }

    #[Test]
    public function forDirective(): void
    {
        $this->writeView('test', '@for($i = 0; $i < 3; $i++){{ $i }}@endfor');
        self::assertSame('012', $this->engine()->render('test'));
    }

    #[Test]
    public function whileDirective(): void
    {
        $this->writeView('test', '<?php $n = 0; ?>@while($n < 3){{ $n }}<?php $n++; ?>@endwhile');
        self::assertSame('012', $this->engine()->render('test'));
    }

    #[Test]
    public function phpDirective(): void
    {
        $this->writeView('test', '@php $x = 42; @endphp{{ $x }}');
        self::assertSame('42', $this->engine()->render('test'));
    }

    // -------------------------------------------------------------------------
    // @include
    // -------------------------------------------------------------------------

    #[Test]
    public function includeDirective(): void
    {
        $this->writeView('parts/footer', '<footer>{{ $title }}</footer>');
        $this->writeView('page', '<main>@include(\'parts.footer\')</main>');
        $result = $this->engine()->render('page', ['title' => 'Hello']);
        self::assertSame('<main><footer>Hello</footer></main>', $result);
    }

    #[Test]
    public function includeWithExtraData(): void
    {
        $this->writeView('parts/greeting', 'Hi {{ $name }}!');
        $this->writeView('page', "@include('parts.greeting', ['name' => 'Bob'])");
        $result = $this->engine()->render('page');
        self::assertSame('Hi Bob!', $result);
    }

    // -------------------------------------------------------------------------
    // Layout inheritance
    // -------------------------------------------------------------------------

    #[Test]
    public function layoutInheritance(): void
    {
        $this->writeView('layouts/main', '<html><body>@yield(\'content\')</body></html>');
        $this->writeView('page', "@extends('layouts.main')\n@section('content')<h1>Hi</h1>@endsection");

        $result = $this->engine()->render('page');
        self::assertStringContainsString('<html><body><h1>Hi</h1></body></html>', str_replace("\n", '', $result));
    }

    #[Test]
    public function layoutWithDefault(): void
    {
        $this->writeView('layouts/base', '<html>@yield(\'title\', \'Default\')</html>');
        $this->writeView('page', "@extends('layouts.base')");

        $result = $this->engine()->render('page');
        self::assertStringContainsString('Default', $result);
    }

    // -------------------------------------------------------------------------
    // Components
    // -------------------------------------------------------------------------

    #[Test]
    public function componentViaRegistry(): void
    {
        $this->writeView('components/alert', '<div class="alert">{{ $message }}</div>');
        $engine = $this->engine();
        $engine->components()->register('alert', 'components.alert');

        $this->writeView('page', "@component('alert', ['message' => 'Done!'])@endcomponent");
        $result = $engine->render('page');
        self::assertSame('<div class="alert">Done!</div>', $result);
    }

    #[Test]
    public function componentAutoDiscover(): void
    {
        $this->writeView('components/badge', '<span>{{ $label }}</span>');
        $engine = $this->engine();
        $engine->components()->autoDiscover($this->base);

        $result = $engine->renderComponent('badge', ['label' => 'New']);
        self::assertSame('<span>New</span>', $result);
    }

    #[Test]
    public function xTagSelfClosing(): void
    {
        $this->writeView('components/icon', '<i class="{{ $name }}"></i>');
        $engine = $this->engine();
        $engine->components()->register('icon', 'components.icon');

        $this->writeView('page', '<x-icon :name="$iconName" />');
        $result = $engine->render('page', ['iconName' => 'star']);
        self::assertSame('<i class="star"></i>', $result);
    }

    #[Test]
    public function xTagWithSlot(): void
    {
        $this->writeView('components/card', '<div class="card">{{ $slot }}</div>');
        $engine = $this->engine();
        $engine->components()->register('card', 'components.card');

        $this->writeView('page', '<x-card>Hello card</x-card>');
        $result = $engine->render('page');
        self::assertStringContainsString('Hello card', $result);
    }

    #[Test]
    public function inlineCallableComponent(): void
    {
        $engine = $this->engine();
        $engine->components()->register(
            'pill',
            static fn(array $d): string => '<span class="pill">' . htmlspecialchars($d['text'], ENT_QUOTES) . '</span>',
        );

        $result = $engine->renderComponent('pill', ['text' => 'Active']);
        self::assertSame('<span class="pill">Active</span>', $result);
    }

    // -------------------------------------------------------------------------
    // Custom directives
    // -------------------------------------------------------------------------

    #[Test]
    public function customDirective(): void
    {
        $engine = $this->engine();
        $engine->directive('uppercase', static fn(string $expr): string => "<?php echo strtoupper({$expr}); ?>");

        $this->writeView('test', "@uppercase(\$word)");
        $result = $engine->render('test', ['word' => 'hello']);
        self::assertSame('HELLO', $result);
    }

    // -------------------------------------------------------------------------
    // Caching
    // -------------------------------------------------------------------------

    #[Test]
    public function compiledFileIsReused(): void
    {
        $this->writeView('test', '{{ $x }}');
        $engine = $this->engine();

        $engine->render('test', ['x' => '1']);

        $compiledFile = $this->compiled . '/test.php';
        self::assertFileExists($compiledFile);

        $mtime = filemtime($compiledFile);

        // Second render — compiled file must NOT be regenerated
        sleep(1);
        $engine->render('test', ['x' => '2']);
        self::assertSame($mtime, filemtime($compiledFile));
    }

    #[Test]
    public function compiledFileIsRefreshedWhenSourceChanges(): void
    {
        $this->writeView('test', '{{ $x }}');
        $engine = $this->engine();
        $engine->render('test', ['x' => 'old']);

        $compiledFile = $this->compiled . '/test.php';
        $oldMtime     = filemtime($compiledFile);

        // Touch source to simulate a change
        sleep(1);
        touch($this->base . '/test.php');

        $engine->render('test', ['x' => 'new']);
        self::assertGreaterThan($oldMtime, filemtime($compiledFile));
    }

    // -------------------------------------------------------------------------
    // Errors
    // -------------------------------------------------------------------------

    #[Test]
    public function throwsOnMissingView(): void
    {
        $this->expectException(ViewException::class);
        $this->engine()->render('nonexistent');
    }

    #[Test]
    public function throwsOnMissingComponent(): void
    {
        $this->expectException(ViewException::class);
        $this->engine()->renderComponent('nope');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function engine(): ViewEngine
    {
        return new ViewEngine($this->base, $this->compiled);
    }

    private function writeView(string $view, string $content): void
    {
        $path = $this->base . '/' . str_replace('.', '/', $view) . '.php';
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $content);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
