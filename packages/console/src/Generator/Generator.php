<?php

declare(strict_types=1);

namespace Nextphp\Console\Generator;

final class Generator
{
    public function __construct(
        private readonly string $basePath,
        private readonly ?string $stubsPath = null,
    ) {
    }

    public function makeController(string $name): string
    {
        return $this->write('app/Http/Controllers/' . $name . '.php', $this->renderStub('controller', $name));
    }

    public function makeModel(string $name): string
    {
        $table = strtolower((string) preg_replace('/[A-Z]/', '_$0', lcfirst($name))) . 's';
        return $this->write('app/Models/' . $name . '.php', $this->renderStub('model', $name, $table));
    }

    public function makeMigration(string $name): string
    {
        $timestamp = date('Y_m_d_His');
        $file      = sprintf('database/migrations/%s_%s.php', $timestamp, $name);
        $class     = $this->toStudlyCase($name);
        // Guess table name: create_users_table → users
        $table     = $this->guessTable($name);

        return $this->write($file, $this->renderStub('migration', $class, $table));
    }

    private function write(string $relativePath, string $content): string
    {
        $path = rtrim($this->basePath, '/') . '/' . ltrim($relativePath, '/');
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Failed to create directory: ' . $dir);
        }
        file_put_contents($path, $content);

        return $path;
    }

    private function renderStub(string $stub, string $class, string $table = ''): string
    {
        $base    = $this->stubsPath ?? dirname(__DIR__, 2) . '/stubs';
        $path    = rtrim($base, '/') . '/' . $stub . '.stub';
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Stub not found: ' . $path);
        }

        return str_replace(['{{CLASS}}', '{{TABLE}}'], [$class, $table], $content);
    }

    private function toStudlyCase(string $name): string
    {
        return str_replace('_', '', ucwords($name, '_'));
    }

    private function guessTable(string $name): string
    {
        // create_users_table → users
        if (preg_match('/^create_(.+?)_table$/', $name, $m)) {
            return $m[1];
        }
        // add_column_to_users → users
        if (preg_match('/_to_(.+)$/', $name, $m)) {
            return $m[1];
        }
        return $name;
    }
}
