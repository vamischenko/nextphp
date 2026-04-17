<?php

declare(strict_types=1);

namespace Nextphp\Console;

final class Application
{
    /** @var array<string, Command> */
    private array $commands = [];

    /**
      * @psalm-mutation-free
     */
    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
      * @psalm-external-mutation-free
     */
    public function add(Command $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv): int
    {
        $commandName = $argv[1] ?? 'list';
        if (in_array($commandName, ['help', '--help', '-h'], true)) {
            return $this->renderHelp();
        }
        if ($commandName === 'list') {
            return $this->renderList();
        }

        $command = $this->commands[$commandName] ?? null;
        if ($command === null) {
            fwrite(STDERR, sprintf("Command \"%s\" not found.\n", $commandName));

            return 1;
        }

        $parsed = Input::parse($argv);

        return $command->handle($parsed['arguments'], $parsed['options']);
    }

    private function renderList(): int
    {
        foreach ($this->commands as $command) {
            fwrite(STDOUT, sprintf("%s\t%s\n", $command->getName(), $command->getDescription()));
        }

        return 0;
    }

    private function renderHelp(): int
    {
        fwrite(STDOUT, "Usage: nextphp <command> [arguments] [--options]\n");
        fwrite(STDOUT, "Commands:\n");
        $this->renderList();

        return 0;
    }
}
