<?php

declare(strict_types=1);

namespace Nextphp\GraphQL;

final class GraphQL
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly Schema $schema,
    ) {
    }

    /**
     * Very small query executor for root fields:
     * query { user } OR { user(id: 1) }
     *
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     */
    public function execute(string $query, array $variables = []): array
    {
        try {
            $field = $this->extractField($query);
            $args = $this->extractArgs($query, $variables);
            $resolver = $this->schema->resolverFor($field);
            $value = $resolver($args);

            return ['data' => [$field => $value]];
        } catch (\Throwable $e) {
            return ['errors' => [['message' => $e->getMessage()]]];
        }
    }

    /**
      * @psalm-pure
     */
    private function extractField(string $query): string
    {
        if (preg_match('/\{\s*([a-zA-Z_][a-zA-Z0-9_]*)/', $query, $match) !== 1) {
            throw new \InvalidArgumentException('Invalid GraphQL query.');
        }

        return $match[1];
    }

    /**
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
       * @psalm-pure
     */
    private function extractArgs(string $query, array $variables): array
    {
        if (preg_match('/\(\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*:\s*([^)]+)\)/', $query, $match) !== 1) {
            return [];
        }

        $name = $match[1];
        $raw = trim($match[2]);
        if (str_starts_with($raw, '$')) {
            $varName = substr($raw, 1);
            if (! array_key_exists($varName, $variables)) {
                throw new \InvalidArgumentException(sprintf('Missing variable "%s".', $varName));
            }

            return [$name => $variables[$varName]];
        }

        if (is_numeric($raw)) {
            return [$name => (int) $raw];
        }

        return [$name => trim($raw, "\"'")];
    }
}
