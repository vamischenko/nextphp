<?php

declare(strict_types=1);

namespace Nextphp\Console;

/**
 * Console output with ANSI colours, aligned tables, and a progress bar.
 */
final class Output
{
    // ANSI colour codes
    public const GREEN  = "\033[32m";
    public const YELLOW = "\033[33m";
    public const RED    = "\033[31m";
    public const CYAN   = "\033[36m";
    public const BOLD   = "\033[1m";
    public const RESET  = "\033[0m";

    /** @var string[] */
    private array $buffer = [];

    private bool $colours;

    /**
      * @psalm-mutation-free
     */
    public function __construct(bool $colours = true)
    {
        // Disable colours when not writing to a real TTY (e.g. tests / pipes)
        $this->colours = $colours && function_exists('posix_isatty') && posix_isatty(STDOUT);
    }

    // -------------------------------------------------------------------------
    // Basic output
    // -------------------------------------------------------------------------

    public function line(string $text = ''): void
    {
        $this->write($text . PHP_EOL);
    }

    public function info(string $text): void
    {
        $this->line($this->colour(self::GREEN, $text));
    }

    public function warn(string $text): void
    {
        $this->line($this->colour(self::YELLOW, $text));
    }

    public function error(string $text): void
    {
        $this->line($this->colour(self::RED, $text));
    }

    public function success(string $text): void
    {
        $this->line($this->colour(self::GREEN . self::BOLD, '✓ ' . $text));
    }

    // -------------------------------------------------------------------------
    // Table with auto-aligned columns
    // -------------------------------------------------------------------------

    /**
     * @param list<string>        $headers
     * @param list<list<string>>  $rows
     */
    public function table(array $headers, array $rows = []): void
    {
        // Support legacy call: table($rows) with no headers
        if ($headers !== [] && is_array($headers[0])) {
            /** @var list<list<string>> $legacyRows */
            $legacyRows = $headers; // @phpstan-ignore-line
            foreach ($legacyRows as $row) {
                $this->line(implode(' | ', $row));
            }
            return;
        }

        $allRows   = array_merge([$headers], $rows);
        $colWidths = [];

        foreach ($allRows as $row) {
            foreach ($row as $i => $cell) {
                $colWidths[$i] = max($colWidths[$i] ?? 0, mb_strlen((string) $cell));
            }
        }

        $separator = '+' . implode('+', array_map(
            static fn(int $w): string => str_repeat('-', $w + 2),
            $colWidths,
        )) . '+';

        $this->line($separator);
        $this->printRow($headers, $colWidths, true);
        $this->line($separator);
        foreach ($rows as $row) {
            $this->printRow($row, $colWidths, false);
        }
        $this->line($separator);
    }

    /**
     * @param list<string> $row
     * @param array<int,int> $colWidths
     */
    private function printRow(array $row, array $colWidths, bool $bold): void
    {
        $cells = [];
        foreach ($colWidths as $i => $width) {
            $cell    = (string) ($row[$i] ?? '');
            $padded  = ' ' . mb_str_pad($cell, $width) . ' ';
            $cells[] = $bold ? $this->colour(self::BOLD, $padded) : $padded;
        }
        $this->line('|' . implode('|', $cells) . '|');
    }

    // -------------------------------------------------------------------------
    // Progress bar
    // -------------------------------------------------------------------------

    public function progress(int $current, int $total, int $width = 40): void
    {
        if ($total <= 0) {
            return;
        }
        $pct   = (int) round($current / $total * 100);
        $done  = (int) round($current / $total * $width);
        $rest  = $width - $done;
        $bar   = str_repeat('█', $done) . str_repeat('░', $rest);
        $line  = sprintf("\r[%s] %3d%% (%d/%d)", $bar, $pct, $current, $total);
        fwrite(STDOUT, $line);
        if ($current >= $total) {
            fwrite(STDOUT, PHP_EOL);
        }
        $this->buffer[] = $line;
    }

    // -------------------------------------------------------------------------
    // Interactive prompt
    // -------------------------------------------------------------------------

    public function ask(string $question, string $default = ''): string
    {
        $prompt = $default !== ''
            ? sprintf('%s [%s]: ', $question, $default)
            : $question . ': ';
        fwrite(STDOUT, $prompt);
        $answer = fgets(STDIN);
        $answer = $answer !== false ? trim($answer) : '';
        return $answer !== '' ? $answer : $default;
    }

    public function confirm(string $question, bool $default = false): bool
    {
        $hint   = $default ? '[Y/n]' : '[y/N]';
        $answer = $this->ask($question . ' ' . $hint);
        if ($answer === '') {
            return $default;
        }
        return in_array(strtolower($answer), ['y', 'yes'], true);
    }

    // -------------------------------------------------------------------------
    // Buffer (used in tests)
    // -------------------------------------------------------------------------

    /** @return string[] */
    public function buffer(): array
    {
        return $this->buffer;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function clearBuffer(): void
    {
        $this->buffer = [];
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function write(string $text): void
    {
        $this->buffer[] = $text;
        fwrite(STDOUT, $text);
    }

    /**
      * @psalm-mutation-free
     */
    private function colour(string $code, string $text): string
    {
        if (!$this->colours) {
            return $text;
        }
        return $code . $text . self::RESET;
    }
}
