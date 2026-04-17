<?php

declare(strict_types=1);

namespace Nextphp\Orm\Seeder;

/**
 * Base class for database seeders.
 *
 * Usage:
 *
 *   class DatabaseSeeder extends Seeder
 *   {
 *       public function run(): void
 *       {
 *           $this->call(UserSeeder::class);
 *           $this->call(PostSeeder::class);
 *       }
 *   }
 *
 *   class UserSeeder extends Seeder
 *   {
 *       public function run(): void
 *       {
 *           UserFactory::new()->count(10)->create();
 *       }
 *   }
 */
abstract class Seeder
{
    private ?SeederRunner $runner = null;

    /**
     * @psalm-impure
     */
    abstract public function run(): void;

    /**
     * Call another seeder class by name.
     *
     * @param class-string<Seeder> $class
     */
    protected function call(string $class): void
    {
        $seeder = new $class();

        if ($this->runner !== null) {
            $seeder->setRunner($this->runner);
        }

        $seeder->run();
    }

    /**
     * Call multiple seeders in sequence.
     *
     * @param list<class-string<Seeder>> $classes
     */
    protected function callAll(array $classes): void
    {
        foreach ($classes as $class) {
            $this->call($class);
        }
    }

    /** @internal used by SeederRunner */
    /**
      * @psalm-external-mutation-free
     */
    public function setRunner(SeederRunner $runner): void
    {
        $this->runner = $runner;
    }
}
