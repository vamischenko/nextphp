<?php

declare(strict_types=1);

namespace Nextphp\View\Tests\Unit;

use Nextphp\View\ViewEngine;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViewEngine::class)]
final class ViewEngineTest extends TestCase
{
    #[Test]
    public function rendersTemplateWithEscapingAndDirectives(): void
    {
        $base = sys_get_temp_dir() . '/nextphp_view_tests';
        @mkdir($base, 0777, true);
        file_put_contents(
            $base . '/home.php',
            <<<'PHP'
<h1>{{ $title }}</h1>
@include('parts.footer')
@if($show)
<ul>
@foreach($items as $item)
<li>{!! $item !!}</li>
@endforeach
</ul>
@endif
PHP
        );
        @mkdir($base . '/parts', 0777, true);
        file_put_contents($base . '/parts/footer.php', '<footer>{{ $title }}</footer>');
        file_put_contents($base . '/layout.php', '<html><body>@yield(\'content\')</body></html>');
        file_put_contents($base . '/child.php', "@extends('layout')\n@section('content')\n<h2>{{ \$title }}</h2>\n@endsection");

        $engine = new ViewEngine($base, $base . '/compiled');
        $result = $engine->render('home', [
            'title' => '<script>x</script>',
            'show' => true,
            'items' => ['<b>a</b>', '<i>b</i>'],
        ]);

        self::assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $result);
        self::assertStringContainsString('<li><b>a</b></li>', $result);
        self::assertStringContainsString('<footer>&lt;script&gt;x&lt;/script&gt;</footer>', $result);

        $layoutResult = $engine->render('child', ['title' => 'Layout Test']);
        self::assertStringContainsString('<html><body><h2>Layout Test</h2></body></html>', str_replace("\n", '', $layoutResult));
    }
}
