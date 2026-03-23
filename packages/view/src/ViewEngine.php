<?php

declare(strict_types=1);

namespace Nextphp\View;

final class ViewEngine
{
    public function __construct(
        private readonly string $viewsPath,
        private readonly ?string $compiledPath = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $templateFile = rtrim($this->viewsPath, '/') . '/' . str_replace('.', '/', $view) . '.php';
        if (! is_file($templateFile)) {
            throw new \RuntimeException(sprintf('View "%s" not found.', $view));
        }

        $compiled = $this->compile((string) file_get_contents($templateFile));
        $target = $this->writeCompiled($view, $compiled);

        ob_start();
        extract($data, EXTR_SKIP);
        include $target;

        return (string) ob_get_clean();
    }

    private function compile(string $template): string
    {
        $template = $this->compileLayouts($template);

        $compiled = $template;
        $compiled = preg_replace('/@include\s*\(\s*[\'"](.+?)[\'"]\s*\)/', '<?php echo $this->render(\'$1\', get_defined_vars()); ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars((string) ($1), ENT_QUOTES, \'UTF-8\'); ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo (string) ($1); ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/@if\s*\((.+?)\)/', '<?php if ($1): ?>', $compiled) ?? $compiled;
        $compiled = str_replace('@endif', '<?php endif; ?>', $compiled);
        $compiled = preg_replace('/@foreach\s*\((.+?)\)/', '<?php foreach ($1): ?>', $compiled) ?? $compiled;
        $compiled = str_replace('@endforeach', '<?php endforeach; ?>', $compiled);

        return $compiled;
    }

    private function compileLayouts(string $template): string
    {
        if (preg_match('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', $template, $match) !== 1) {
            return $template;
        }

        $layoutView = $match[1];
        $layoutFile = rtrim($this->viewsPath, '/') . '/' . str_replace('.', '/', $layoutView) . '.php';
        if (! is_file($layoutFile)) {
            return $template;
        }

        $layout = (string) file_get_contents($layoutFile);
        $sections = [];

        preg_match_all('/@section\s*\(\s*[\'"](.+?)[\'"]\s*\)(.*?)@endsection/s', $template, $sectionMatches, PREG_SET_ORDER);
        foreach ($sectionMatches as $sectionMatch) {
            $sections[$sectionMatch[1]] = trim($sectionMatch[2]);
        }

        foreach ($sections as $name => $content) {
            $layout = preg_replace('/@yield\s*\(\s*[\'"]' . preg_quote($name, '/') . '[\'"]\s*\)/', $content, $layout) ?? $layout;
        }

        return preg_replace('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', '', $layout) ?? $layout;
    }

    private function writeCompiled(string $view, string $compiled): string
    {
        if ($this->compiledPath === null) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'nextphp_view_');
            if ($tmpFile === false) {
                throw new \RuntimeException('Failed to create temp file for compiled view.');
            }
            file_put_contents($tmpFile, $compiled);

            return $tmpFile;
        }

        $dir = rtrim($this->compiledPath, '/');
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Failed to create compiled views directory.');
        }

        $path = $dir . '/' . str_replace(['.', '/'], '_', $view) . '.php';
        file_put_contents($path, $compiled);

        return $path;
    }
}
