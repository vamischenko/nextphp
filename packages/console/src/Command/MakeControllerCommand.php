<?php

declare(strict_types=1);

namespace Nextphp\Console\Command;

use Nextphp\Console\Command;
use Nextphp\Console\Generator\Generator;
use Nextphp\Console\Output;

final class MakeControllerCommand extends Command
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(private readonly Generator $generator)
    {
        parent::__construct('make:controller', 'Create a new controller class');
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
            $output->error('Usage: make:controller <ControllerName>');
            return 1;
        }

        // Ensure suffix
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $path = $this->generator->makeController($name);
        $output->success(sprintf('Controller created: %s', $path));

        return 0;
    }
}
