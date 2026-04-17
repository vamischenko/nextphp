<?php

declare(strict_types=1);

namespace Nextphp\Console;

/**
 * @psalm-immutable
 */
final class Input
{
    /** @param array<int, string> $argv */
    /**
      * @psalm-pure
     */
    public static function parse(array $argv): array
    {
        $arguments = [];
        $options = [];

        foreach (array_slice($argv, 2) as $token) {
            if (str_starts_with($token, '--')) {
                $withoutPrefix = substr($token, 2);
                if (str_contains($withoutPrefix, '=')) {
                    [$k, $v] = explode('=', $withoutPrefix, 2);
                    $options[$k] = $v;
                } else {
                    $options[$withoutPrefix] = true;
                }
                continue;
            }
            $arguments[] = $token;
        }

        return ['arguments' => $arguments, 'options' => $options];
    }
}
