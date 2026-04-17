<?php

declare(strict_types=1);

namespace Nextphp\Console\Command;

use Nextphp\Console\Command;
use Nextphp\Console\Generator\Generator;
use Nextphp\Console\Output;

final class MakeModelCommand extends Command
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(private readonly Generator $generator)
    {
        parent::__construct('make:model', 'Create a new ORM model class');
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
            $output->error('Usage: make:model <ModelName>');
            return 1;
        }

        $path = $this->generator->makeModel($name);
        $output->success(sprintf('Model created: %s', $path));

        // Optionally generate migration alongside: --migration flag
        if (isset($options['migration'])) {
            $table       = $this->toSnakeCase($name) . 's';
            $migName     = 'create_' . $table . '_table';
            $migPath     = $this->generator->makeMigration($migName);
            $output->success(sprintf('Migration created: %s', $migPath));
        }

        return 0;
    }

    /**
      * @psalm-pure
     */
    private function toSnakeCase(string $name): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '_$0', lcfirst($name)));
    }
}
