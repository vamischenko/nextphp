<?php

declare(strict_types=1);

namespace Nextphp\View;

/**
 * Registry that maps component names to view paths or PHP callables.
 *
 * Usage:
 *   $registry->register('alert', 'components.alert');         // view path
 *   $registry->register('badge', fn(array $d) => '<span>'.$d['label'].'</span>'); // inline
 */
final class ComponentRegistry
{
    /** @var array<string, string|callable(array<string,mixed>): string> */
    private array $components = [];

    /**
     * @param string|callable(array<string,mixed>): string $view view dot-path or callable
     */
    public function register(string $name, string|callable $view): void
    {
        $this->components[$name] = $view;
    }

    public function has(string $name): bool
    {
        return isset($this->components[$name]);
    }

    /**
     * @return string|callable(array<string,mixed>): string
     */
    public function get(string $name): string|callable
    {
        if (!$this->has($name)) {
            throw new Exception\ViewException(sprintf('Component "%s" is not registered.', $name));
        }
        return $this->components[$name];
    }

    /**
     * Auto-register all views found in a directory as components.
     * E.g. "components/alert.php" → component "alert"
     *      "components/forms/input.php" → component "forms.input"
     */
    public function autoDiscover(string $viewsPath, string $namespace = 'components'): void
    {
        $dir = rtrim($viewsPath, '/') . '/' . str_replace('.', '/', $namespace);
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace($dir . '/', '', $file->getPathname());
            $name     = str_replace(['/', '.php'], ['.', ''], $relative);

            $viewKey = $namespace . '.' . $name;
            $this->components[$name] = $viewKey;
        }
    }
}
