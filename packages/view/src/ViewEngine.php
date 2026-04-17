<?php

declare(strict_types=1);

namespace Nextphp\View;

use Nextphp\View\Exception\ViewException;

/**
 * Blade-like template engine.
 *
 * Features:
 *  - Template compilation with file-based cache (invalidated by mtime)
 *  - Layout inheritance (@extends / @section / @yield)
 *  - Partials (@include)
 *  - Components: @component / <x-name> tags + ComponentRegistry
 *  - Custom directives via Compiler::directive()
 *  - All standard control directives (@if, @foreach, @for, @while, @forelse, @unless, @switch, @php)
 */
final class ViewEngine
{
    private readonly Compiler $compiler;
    private readonly ComponentRegistry $components;

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $viewsPath,
        private readonly ?string $compiledPath = null,
        ?Compiler $compiler = null,
        ?ComponentRegistry $components = null,
    ) {
        $this->compiler   = $compiler ?? new Compiler();
        $this->components = $components ?? new ComponentRegistry();
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Render a view by dot-path (e.g. "pages.home") with optional data.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $templateFile = $this->resolvePath($view);

        if (!is_file($templateFile)) {
            throw new ViewException(sprintf('View "%s" not found at "%s".', $view, $templateFile));
        }

        $source   = (string) file_get_contents($templateFile);
        $compiled = $this->getCompiledPath($view, $templateFile, $source);

        return $this->evaluate($compiled, $view, $data);
    }

    /**
     * Render a registered component by name.
     *
     * @param array<string, mixed> $data
     */
    public function renderComponent(string $name, array $data = []): string
    {
        if (!$this->components->has($name)) {
            // Auto-discover: try "components.<name>" view
            $guessedView = 'components.' . $name;
            $guessedFile = $this->resolvePath($guessedView);
            if (is_file($guessedFile)) {
                $this->components->register($name, $guessedView);
            } else {
                throw new ViewException(sprintf('Component "%s" not found.', $name));
            }
        }

        $entry = $this->components->get($name);

        if (is_callable($entry)) {
            return ($entry)($data);
        }

        return $this->render($entry, $data);
    }

    /**
     * Register a custom directive (delegated to Compiler).
     *
     * @param callable(string): string $handler
       * @psalm-external-mutation-free
     */
    public function directive(string $name, callable $handler): void
    {
        $this->compiler->directive($name, $handler);
    }

    /**
     * Access the component registry to register components manually.
     */
    public function components(): ComponentRegistry
    {
        return $this->components;
    }

    // -------------------------------------------------------------------------
    // Compilation & caching
    // -------------------------------------------------------------------------

    /**
     * Returns the path to the compiled file (creates/refreshes if needed).
     */
    private function getCompiledPath(string $view, string $templateFile, string $source): string
    {
        $compiledFile = $this->compiledFilePath($view);

        // Use cache if it exists and is newer than the source template
        if (
            $compiledFile !== null
            && is_file($compiledFile)
            && filemtime($compiledFile) >= filemtime($templateFile)
        ) {
            return $compiledFile;
        }

        // Handle @extends layout before compiling the child
        $compiled = $this->resolveLayout($source);
        $compiled = $this->compiler->compile($compiled);

        return $this->writeCompiled($view, $compiled);
    }

    /**
     * Resolve @extends layout: extract sections from child, inject into layout.
     */
    private function resolveLayout(string $source): string
    {
        if (preg_match('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', $source, $match) !== 1) {
            return $source;
        }

        $layoutView = $match[1];
        $layoutFile = $this->resolvePath($layoutView);

        if (!is_file($layoutFile)) {
            throw new ViewException(sprintf('Layout "%s" not found.', $layoutView));
        }

        $layout   = (string) file_get_contents($layoutFile);
        $sections = $this->extractSections($source);

        // Replace @yield('name') in layout with section content
        foreach ($sections as $name => $content) {
            $layout = preg_replace(
                '/@yield\s*\(\s*[\'"]' . preg_quote($name, '/') . '[\'"]\s*(?:,\s*[\'"][^"\']*[\'"]\s*)?\)/',
                $content,
                $layout,
            ) ?? $layout;
        }

        // Remove the @extends directive from the merged result
        return preg_replace('/@extends\s*\(\s*[\'"].+?[\'"]\s*\)/', '', $layout) ?? $layout;
    }

    /**
     * Extract @section('name') ... @endsection blocks from a template.
     *
     * @return array<string, string>
       * @psalm-pure
     */
    private function extractSections(string $source): array
    {
        $sections = [];
        preg_match_all(
            '/@section\s*\(\s*[\'"](.+?)[\'"]\s*\)(.*?)@endsection/s',
            $source,
            $matches,
            PREG_SET_ORDER,
        );
        foreach ($matches as $m) {
            $sections[$m[1]] = trim($m[2]);
        }
        return $sections;
    }

    // -------------------------------------------------------------------------
    // Evaluation
    // -------------------------------------------------------------------------

    /**
     * Execute a compiled template file and capture its output.
     *
     * @param array<string, mixed> $data
     */
    private function evaluate(string $compiledFile, string $view, array $data): string
    {
        $__engine   = $this;
        $__sections = [];
        $__slots    = [];

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $compiledFile;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new ViewException(
                sprintf('Error rendering view "%s": %s', $view, $e->getMessage()),
                0,
                $e,
            );
        }

        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
      * @psalm-mutation-free
     */
    private function resolvePath(string $view): string
    {
        return rtrim($this->viewsPath, '/') . '/' . str_replace('.', '/', $view) . '.php';
    }

    /**
      * @psalm-mutation-free
     */
    private function compiledFilePath(string $view): ?string
    {
        if ($this->compiledPath === null) {
            return null;
        }
        return rtrim($this->compiledPath, '/') . '/' . str_replace(['.', '/'], '_', $view) . '.php';
    }

    private function writeCompiled(string $view, string $compiled): string
    {
        $target = $this->compiledFilePath($view);

        if ($target === null) {
            // No cache dir — write to a temp file
            $tmp = tempnam(sys_get_temp_dir(), 'nextphp_view_');
            if ($tmp === false) {
                throw new ViewException('Failed to create temp file for compiled view.');
            }
            file_put_contents($tmp, $compiled);
            return $tmp;
        }

        $dir = dirname($target);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new ViewException(sprintf('Cannot create compiled views directory "%s".', $dir));
        }

        file_put_contents($target, $compiled);
        return $target;
    }
}
