<?php

declare(strict_types=1);

namespace Nextphp\Orm\Seeder;

/**
 * Discovers and runs seeders.
 *
 * Usage:
 *   $runner = new SeederRunner();
 *   $runner->run(DatabaseSeeder::class);
 *
 *   // Or auto-discover all seeders in a directory:
 *   $runner->discover('/path/to/database/seeders')->runAll();
 */
final class SeederRunner
{
    /** @var list<class-string<Seeder>> */
    private array $seeders = [];

    /**
     * Run a single seeder by class name.
     *
     * @param class-string<Seeder> $class
     */
    public function run(string $class): void
    {
        $seeder = new $class();
        $seeder->setRunner($this);
        $seeder->run();
    }

    /**
     * Register seeders to run later with runAll().
     *
     * @param class-string<Seeder> ...$classes
     */
    public function register(string ...$classes): static
    {
        foreach ($classes as $class) {
            $this->seeders[] = $class;
        }
        return $this;
    }

    /**
     * Run all registered seeders in order.
     */
    public function runAll(): void
    {
        foreach ($this->seeders as $class) {
            $this->run($class);
        }
    }

    /**
     * Auto-discover Seeder subclasses from PHP files in a directory.
     * Files must follow PSR-4: class name = file name.
     */
    public function discover(string $directory, string $namespace = 'Database\\Seeders'): static
    {
        if (!is_dir($directory)) {
            return $this;
        }

        foreach (glob(rtrim($directory, '/') . '/*.php') ?: [] as $file) {
            $class = $namespace . '\\' . basename($file, '.php');

            if (!class_exists($class)) {
                require_once $file;
            }

            if (class_exists($class) && is_subclass_of($class, Seeder::class)) {
                /** @var class-string<Seeder> $class */
                $this->seeders[] = $class;
            }
        }

        return $this;
    }
}
