<?php

declare(strict_types=1);

namespace Nextphp\Console;

final class Output
{
    /** @var string[] */
    private array $buffer = [];

    public function line(string $text): void
    {
        $this->buffer[] = $text;
        fwrite(STDOUT, $text . PHP_EOL);
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    public function table(array $rows): void
    {
        foreach ($rows as $row) {
            $this->line(implode(' | ', $row));
        }
    }

    public function progress(int $current, int $total): void
    {
        $this->line(sprintf('[%d/%d]', $current, $total));
    }

    /**
     * @return string[]
     */
    public function buffer(): array
    {
        return $this->buffer;
    }
}
