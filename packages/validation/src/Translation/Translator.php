<?php

declare(strict_types=1);

namespace Nextphp\Validation\Translation;

/**
  * @psalm-immutable
 */
final class Translator
{
    /** @var array<string, array<string, string|array<string, string>>> */
    private array $catalogues;

    /**
     * @param array<string, array<string, string|array<string, string>>> $catalogues
       * @psalm-mutation-free
     */
    public function __construct(array $catalogues)
    {
        $this->catalogues = $catalogues;
    }

    /**
     * @param array<string, scalar> $params
       * @psalm-mutation-free
     */
    public function trans(string $locale, string $key, array $params = [], ?string $fallback = null): string
    {
        $message = $this->lookup($locale, $key) ?? $fallback ?? $key;

        foreach ($params as $k => $v) {
            $message = str_replace(':' . $k, (string) $v, $message);
        }

        return $message;
    }

    /**
      * @psalm-mutation-free
     */
    private function lookup(string $locale, string $key): ?string
    {
        $cat = $this->catalogues[$locale] ?? null;
        if (!is_array($cat)) {
            return null;
        }

        // Support dot keys: validation.required, validation.min.string etc.
        $parts = explode('.', $key);
        $cur = $cat;
        foreach ($parts as $part) {
            if (!is_array($cur) || !array_key_exists($part, $cur)) {
                return null;
            }
            $cur = $cur[$part];
        }

        return is_string($cur) ? $cur : null;
    }
}

