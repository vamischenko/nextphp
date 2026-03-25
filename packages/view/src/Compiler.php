<?php

declare(strict_types=1);

namespace Nextphp\View;

/**
 * Compiles Blade-like template syntax into executable PHP.
 *
 * Supported directives:
 *   {{ $expr }}         — escaped output
 *   {!! $expr !!}       — raw output
 *   @if / @elseif / @else / @endif
 *   @unless / @endunless
 *   @foreach / @endforeach
 *   @forelse / @empty / @endforelse
 *   @for / @endfor
 *   @while / @endwhile
 *   @switch / @case / @default / @endswitch
 *   @php / @endphp
 *   @include('view', [...])
 *   @extends('layout')
 *   @section('name') ... @endsection
 *   @yield('name', 'default')
 *   @component('name', [...]) ... @endcomponent
 *   @slot('name') ... @endslot
 *   <x-component-name :prop="$val"> ... </x-component-name>
 *   @directive(args)    — custom directives
 */
final class Compiler
{
    /** @var array<string, callable(string): string> */
    private array $customDirectives = [];

    /**
     * Register a custom directive.
     *
     * @param callable(string): string $handler receives the raw argument string, returns PHP code
     */
    public function directive(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
    }

    public function compile(string $source): string
    {
        $result = $source;

        // 1. @php / @endphp — raw PHP blocks (process before other directives)
        $result = preg_replace('/@php\b/', '<?php', $result) ?? $result;
        $result = preg_replace('/@endphp\b/', '?>', $result) ?? $result;

        // 2. Layouts
        $result = $this->compileYield($result);
        $result = $this->compileSection($result);

        // 3. Echo
        $result = $this->compileEcho($result);

        // 4. Control structures
        $result = $this->compileIf($result);
        $result = $this->compileUnless($result);
        $result = $this->compileSwitch($result);
        $result = $this->compileForEach($result);
        $result = $this->compileForElse($result);
        $result = $this->compileFor($result);
        $result = $this->compileWhile($result);

        // 5. Includes
        $result = $this->compileInclude($result);

        // 6. Components
        $result = $this->compileComponents($result);
        $result = $this->compileXTags($result);

        // 7. Custom directives
        $result = $this->compileCustomDirectives($result);

        return $result;
    }

    // -------------------------------------------------------------------------
    // Echo
    // -------------------------------------------------------------------------

    private function compileEcho(string $template): string
    {
        // {!! raw !!}
        $template = preg_replace(
            '/\{!!\s*(.+?)\s*!!\}/s',
            '<?php echo ($1); ?>',
            $template,
        ) ?? $template;

        // {{ escaped }}
        $template = preg_replace(
            '/\{\{\s*(.+?)\s*\}\}/s',
            "<?php echo htmlspecialchars((string) ($1), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>",
            $template,
        ) ?? $template;

        return $template;
    }

    // -------------------------------------------------------------------------
    // @if / @elseif / @else / @endif
    // -------------------------------------------------------------------------

    private function compileIf(string $template): string
    {
        $template = preg_replace('/@if\s*\((.+?)\)/s', '<?php if ($1): ?>', $template) ?? $template;
        $template = preg_replace('/@elseif\s*\((.+?)\)/s', '<?php elseif ($1): ?>', $template) ?? $template;
        $template = str_replace('@else', '<?php else: ?>', $template);
        $template = str_replace('@endif', '<?php endif; ?>', $template);
        return $template;
    }

    // -------------------------------------------------------------------------
    // @unless / @endunless
    // -------------------------------------------------------------------------

    private function compileUnless(string $template): string
    {
        $template = preg_replace('/@unless\s*\((.+?)\)/s', '<?php if (!($1)): ?>', $template) ?? $template;
        $template = str_replace('@endunless', '<?php endif; ?>', $template);
        return $template;
    }

    // -------------------------------------------------------------------------
    // @switch / @case / @default / @endswitch
    // -------------------------------------------------------------------------

    private function compileSwitch(string $template): string
    {
        $template = preg_replace('/@switch\s*\((.+?)\)/s', '<?php switch ($1): ?>', $template) ?? $template;
        $template = preg_replace('/@case\s*\((.+?)\)/s', '<?php case $1: ?>', $template) ?? $template;
        $template = str_replace('@default', '<?php default: ?>', $template);
        $template = str_replace(['@break', '@endswitch'], ['<?php break; ?>', '<?php endswitch; ?>'], $template);
        return $template;
    }

    // -------------------------------------------------------------------------
    // @foreach / @endforeach
    // -------------------------------------------------------------------------

    private function compileForEach(string $template): string
    {
        $template = preg_replace('/@foreach\s*\((.+?)\)/s', '<?php foreach ($1): ?>', $template) ?? $template;
        $template = str_replace('@endforeach', '<?php endforeach; ?>', $template);
        return $template;
    }

    // -------------------------------------------------------------------------
    // @forelse / @empty / @endforelse
    // -------------------------------------------------------------------------

    private function compileForElse(string $template): string
    {
        // @forelse ($items as $item) ... @empty ... @endforelse
        $template = preg_replace(
            '/@forelse\s*\((.+?)\)/s',
            '<?php $__forElseEmpty = true; foreach ($1): $__forElseEmpty = false; ?>',
            $template,
        ) ?? $template;
        $template = str_replace('@empty', '<?php endforeach; if ($__forElseEmpty): ?>', $template);
        $template = str_replace('@endforelse', '<?php endif; ?>', $template);
        return $template;
    }

    // -------------------------------------------------------------------------
    // @for / @endfor
    // -------------------------------------------------------------------------

    private function compileFor(string $template): string
    {
        $template = preg_replace('/@for\s*\((.+?)\)/s', '<?php for ($1): ?>', $template) ?? $template;
        $template = str_replace('@endfor', '<?php endfor; ?>', $template);
        return $template;
    }

    // -------------------------------------------------------------------------
    // @while / @endwhile
    // -------------------------------------------------------------------------

    private function compileWhile(string $template): string
    {
        $template = preg_replace('/@while\s*\((.+?)\)/s', '<?php while ($1): ?>', $template) ?? $template;
        $template = str_replace('@endwhile', '<?php endwhile; ?>', $template);
        return $template;
    }

    // -------------------------------------------------------------------------
    // @include
    // -------------------------------------------------------------------------

    private function compileInclude(string $template): string
    {
        // @include('view.name', ['key' => 'val'])
        $template = preg_replace_callback(
            '/@include\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*(.+?))?\s*\)/',
            static function (array $m): string {
                $view = $m[1];
                $data = isset($m[2]) && trim($m[2]) !== '' ? $m[2] : '[]';
                return "<?php echo \$__engine->render('{$view}', array_merge(get_defined_vars(), {$data})); ?>";
            },
            $template,
        ) ?? $template;

        return $template;
    }

    // -------------------------------------------------------------------------
    // @yield / @section / @endsection  (for layout merging — handled at render time)
    // -------------------------------------------------------------------------

    private function compileYield(string $template): string
    {
        // @yield('name') or @yield('name', 'default')
        $template = preg_replace_callback(
            '/@yield\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*[\'"](.+?)[\'"]\s*)?\)/',
            static function (array $m): string {
                $name    = $m[1];
                $default = isset($m[2]) ? htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8') : '';
                return "<?php echo \$__sections['{$name}'] ?? '{$default}'; ?>";
            },
            $template,
        ) ?? $template;

        return $template;
    }

    private function compileSection(string $template): string
    {
        // @section('name') ... @endsection  →  kept as-is; extracted by ViewEngine
        return $template;
    }

    // -------------------------------------------------------------------------
    // @component / @slot / @endcomponent / @endslot
    // -------------------------------------------------------------------------

    private function compileComponents(string $template): string
    {
        // @slot('name') ... @endslot
        $template = preg_replace_callback(
            '/@slot\s*\(\s*[\'"](.+?)[\'"]\s*\)(.*?)@endslot/s',
            static function (array $m): string {
                $name    = $m[1];
                $content = addslashes(trim($m[2]));
                return "<?php \$__slots['{$name}'] = '{$content}'; ?>";
            },
            $template,
        ) ?? $template;

        // @component('name', ['prop' => $val]) ... @endcomponent
        $template = preg_replace_callback(
            '/@component\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*(.+?))?\s*\)(.*?)@endcomponent/s',
            static function (array $m): string {
                $name    = $m[1];
                $data    = isset($m[2]) && trim($m[2]) !== '' ? $m[2] : '[]';
                $content = addslashes(trim($m[3]));
                return "<?php echo \$__engine->renderComponent('{$name}', array_merge({$data}, ['slot' => '{$content}', 'slots' => \$__slots ?? []])); \$__slots = []; ?>";
            },
            $template,
        ) ?? $template;

        return $template;
    }

    // -------------------------------------------------------------------------
    // <x-component-name :prop="$expr" attr="literal"> ... </x-component-name>
    // -------------------------------------------------------------------------

    private function compileXTags(string $template): string
    {
        // Self-closing: <x-alert :message="$msg" />
        $template = preg_replace_callback(
            '/<x-([\w\-]+)((?:\s+[^>]*?)?)\s*\/>/',
            static function (array $m): string {
                $name  = $m[1];
                $attrs = self::parseXAttributes($m[2]);
                return "<?php echo \$__engine->renderComponent('{$name}', {$attrs}); ?>";
            },
            $template,
        ) ?? $template;

        // With content: <x-card :title="$t">...</x-card>
        $template = preg_replace_callback(
            '/<x-([\w\-]+)((?:\s+[^>]*?)?)\s*>(.*?)<\/x-\1>/s',
            static function (array $m): string {
                $name    = $m[1];
                $attrs   = self::parseXAttributes($m[2]);
                $content = addslashes(trim($m[3]));
                return "<?php echo \$__engine->renderComponent('{$name}', array_merge({$attrs}, ['slot' => '{$content}'])); ?>";
            },
            $template,
        ) ?? $template;

        return $template;
    }

    /**
     * Convert " :prop=\"$expr\" attr=\"literal\" " into a PHP array string.
     */
    private static function parseXAttributes(string $attrs): string
    {
        $parts = [];
        // :prop="$expr" — dynamic
        preg_match_all('/:(\w+)=["\']([^"\']+)["\']/', $attrs, $dynamic, PREG_SET_ORDER);
        foreach ($dynamic as $d) {
            $parts[] = "'{$d[1]}' => {$d[2]}";
        }
        // attr="value" — static (no colon)
        preg_match_all('/(?<!:)\b(\w+)=["\']([^"\']*)["\']/', $attrs, $static, PREG_SET_ORDER);
        foreach ($static as $s) {
            $val     = addslashes($s[2]);
            $parts[] = "'{$s[1]}' => '{$val}'";
        }
        return '[' . implode(', ', $parts) . ']';
    }

    // -------------------------------------------------------------------------
    // Custom directives
    // -------------------------------------------------------------------------

    private function compileCustomDirectives(string $template): string
    {
        foreach ($this->customDirectives as $name => $handler) {
            $template = preg_replace_callback(
                '/@' . preg_quote($name, '/') . '\s*(?:\(([^)]*)\))?/',
                static function (array $m) use ($handler): string {
                    return $handler($m[1] ?? '');
                },
                $template,
            ) ?? $template;
        }
        return $template;
    }
}
