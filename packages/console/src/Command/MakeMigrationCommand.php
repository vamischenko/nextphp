<?php

declare(strict_types=1);

namespace Nextphp\Console\Command;

use Nextphp\Console\Command;
use Nextphp\Console\Generator\Generator;
use Nextphp\Console\Output;

final class MakeMigrationCommand extends Command
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(private readonly Generator $generator)
    {
        parent::__construct('make:migration', 'Create a new database migration');
    }

    /**
     * @param array<int, string>   $arguments
     * @param array<string, mixed> $options
     */
    public function handle(array $arguments, array $options = []): int
    {
        $output = new Output();
        $name   = $arguments[0] ?? '';

        if ($name === '') {
            $output->error('Usage: make:migration <migration_name>');
            return 1;
        }

        // Normalise: spaces/dashes → underscores, lowercase
        $name = strtolower(str_replace([' ', '-'], '_', $name));

        $path = $this->generator->makeMigration($name);
        $output->success(sprintf('Migration created: %s', $path));

        return 0;
    }
}
