<?php

declare(strict_types=1);

namespace Nextphp\Orm\Query;

use Nextphp\Orm\Connection\MongoConnection;

/**
 * Fluent query builder for MongoDB.
 *
 * Provides an API similar to Builder (SQL) but compiles to MongoDB
 * filter/options documents instead of SQL strings.
 *
 * Usage:
 *   $users = (new MongoBuilder($conn))
 *       ->collection('users')
 *       ->where('age', '>=', 18)
 *       ->orderBy('name')
 *       ->limit(10)
 *       ->get();
 */
final class MongoBuilder
{
    private string $collection = '';

    /** @var array<string, mixed> */
    private array $filter = [];

    /** @var array<string, int> */
    private array $sort = [];

    /** @var string[] */
    private array $projection = [];

    private ?int $limit = null;

    private ?int $skip = null;

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly MongoConnection $connection,
    ) {
    }

    // -------------------------------------------------------------------------
    // Collection selection
    // -------------------------------------------------------------------------

    public function collection(string $name): static
    {
        $clone             = clone $this;
        $clone->collection = $name;

        return $clone;
    }

    /** Alias for collection() — mirrors Builder::table() */
    public function table(string $name): static
    {
        return $this->collection($name);
    }

    // -------------------------------------------------------------------------
    // Filtering
    // -------------------------------------------------------------------------

    /**
     * Add a filter condition.
     *
     * @param mixed $value
     */
    public function where(string $field, string $operator = '=', mixed $value = null): static
    {
        $clone = clone $this;

        $mongoOperator = match ($operator) {
            '=', '=='  => null,
            '!='       => '$ne',
            '>'        => '$gt',
            '>='       => '$gte',
            '<'        => '$lt',
            '<='       => '$lte',
            'like'     => 'regex',
            default    => null,
        };

        if ($mongoOperator === null) {
            $clone->filter[$field] = $value;
        } elseif ($mongoOperator === 'regex') {
            // Convert SQL LIKE pattern to regex: % → .*, _ → .
            $pattern               = str_replace(['%', '_'], ['.*', '.'], (string) $value);
            $clone->filter[$field] = new \MongoDB\BSON\Regex($pattern, 'i');
        } else {
            $clone->filter[$field] = [$mongoOperator => $value];
        }

        return $clone;
    }

    /**
     * @param mixed[] $values
     */
    public function whereIn(string $field, array $values): static
    {
        $clone                = clone $this;
        $clone->filter[$field] = ['$in' => $values];

        return $clone;
    }

    /**
     * @param mixed[] $values
     */
    public function whereNotIn(string $field, array $values): static
    {
        $clone                = clone $this;
        $clone->filter[$field] = ['$nin' => $values];

        return $clone;
    }

    public function whereNull(string $field): static
    {
        $clone                = clone $this;
        $clone->filter[$field] = null;

        return $clone;
    }

    public function whereNotNull(string $field): static
    {
        $clone                = clone $this;
        $clone->filter[$field] = ['$ne' => null];

        return $clone;
    }

    /**
     * @param mixed $min
     * @param mixed $max
     */
    public function whereBetween(string $field, mixed $min, mixed $max): static
    {
        $clone                = clone $this;
        $clone->filter[$field] = ['$gte' => $min, '$lte' => $max];

        return $clone;
    }

    /**
     * Raw MongoDB filter document merged into current filter.
     *
     * @param array<string, mixed> $filter
     */
    public function whereRaw(array $filter): static
    {
        $clone         = clone $this;
        $clone->filter = array_merge($clone->filter, $filter);

        return $clone;
    }

    // -------------------------------------------------------------------------
    // Sorting / Pagination
    // -------------------------------------------------------------------------

    public function orderBy(string $field, string $direction = 'ASC'): static
    {
        $clone               = clone $this;
        $clone->sort[$field] = strtoupper($direction) === 'ASC' ? 1 : -1;

        return $clone;
    }

    public function orderByDesc(string $field): static
    {
        return $this->orderBy($field, 'DESC');
    }

    public function limit(int $value): static
    {
        $clone        = clone $this;
        $clone->limit = $value;

        return $clone;
    }

    public function skip(int $value): static
    {
        $clone       = clone $this;
        $clone->skip = $value;

        return $clone;
    }

    public function offset(int $value): static
    {
        return $this->skip($value);
    }

    /**
     * @param string[] $fields
     */
    public function select(array $fields): static
    {
        $clone             = clone $this;
        $clone->projection = $fields;

        return $clone;
    }

    // -------------------------------------------------------------------------
    // Query execution
    // -------------------------------------------------------------------------

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $start   = microtime(true);
        $options = $this->buildOptions();

        $cursor = $this->connection->collection($this->collection)->find($this->filter, $options);
        $rows   = [];

        foreach ($cursor as $doc) {
            $rows[] = $this->documentToArray($doc);
        }

        $this->connection->logOperation([
            'collection' => $this->collection,
            'operation'  => 'find',
            'filter'     => $this->filter,
            'options'    => $options,
            'time'       => round((microtime(true) - $start) * 1000, 2),
        ]);

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $start   = microtime(true);
        $options = $this->buildOptions();

        $doc = $this->connection->collection($this->collection)->findOne($this->filter, $options);

        $this->connection->logOperation([
            'collection' => $this->collection,
            'operation'  => 'findOne',
            'filter'     => $this->filter,
            'options'    => $options,
            'time'       => round((microtime(true) - $start) * 1000, 2),
        ]);

        return $doc !== null ? $this->documentToArray($doc) : null;
    }

    public function count(): int
    {
        $start = microtime(true);
        $count = $this->connection->collection($this->collection)->countDocuments($this->filter);

        $this->connection->logOperation([
            'collection' => $this->collection,
            'operation'  => 'countDocuments',
            'filter'     => $this->filter,
            'time'       => round((microtime(true) - $start) * 1000, 2),
        ]);

        return $count;
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Insert a single document.
     *
     * @param array<string, mixed> $data
     */
    public function insert(array $data): string|false
    {
        $start  = microtime(true);
        $result = $this->connection->collection($this->collection)->insertOne($data);

        $this->connection->logOperation([
            'collection' => $this->collection,
            'operation'  => 'insertOne',
            'document'   => $data,
            'time'       => round((microtime(true) - $start) * 1000, 2),
        ]);

        $id = $result->getInsertedId();

        return $id !== null ? (string) $id : false;
    }

    /**
     * Insert multiple documents.
     *
     * @param array<int, array<string, mixed>> $documents
     * @return string[] inserted IDs
     */
    public function insertMany(array $documents): array
    {
        $start  = microtime(true);
        $result = $this->connection->collection($this->collection)->insertMany($documents);

        $this->connection->logOperation([
            'collection' => $this->collection,
            'operation'  => 'insertMany',
            'count'      => count($documents),
            'time'       => round((microtime(true) - $start) * 1000, 2),
        ]);

        return array_map('strval', $result->getInsertedIds());
    }

    /**
     * Update documents matching the current filter.
     *
     * @param array<string, mixed> $data
     */
    public function update(array $data): int
    {
        $start  = microtime(true);
        $result = $this->connection->collection($this->collection)
            ->updateMany($this->filter, ['$set' => $data]);

        $this->connection->logOperation([
            'collection' => $this->collection,
            'operation'  => 'updateMany',
            'filter'     => $this->filter,
            'update'     => ['$set' => $data],
            'time'       => round((microtime(true) - $start) * 1000, 2),
        ]);

        return $result->getModifiedCount();
    }

    /**
     * Replace a single document matching the current filter.
     *
     * @param array<string, mixed> $data
     */
    public function replace(array $data): int
    {
        $start  = microtime(true);
        $result = $this->connection->collection($this->collection)
            ->replaceOne($this->filter, $data);

        $this->connection->logOperation([
            'collection' => $this->collection,
            'operation'  => 'replaceOne',
            'filter'     => $this->filter,
            'time'       => round((microtime(true) - $start) * 1000, 2),
        ]);

        return $result->getModifiedCount();
    }

    /**
     * Delete documents matching the current filter.
     */
    public function delete(): int
    {
        $start  = microtime(true);
        $result = $this->connection->collection($this->collection)->deleteMany($this->filter);

        $this->connection->logOperation([
            'collection' => $this->collection,
            'operation'  => 'deleteMany',
            'filter'     => $this->filter,
            'time'       => round((microtime(true) - $start) * 1000, 2),
        ]);

        return $result->getDeletedCount();
    }

    /**
     * Run an aggregation pipeline.
     *
     * @param array<int, array<string, mixed>> $pipeline
     * @return array<int, array<string, mixed>>
     */
    public function aggregate(array $pipeline): array
    {
        $start  = microtime(true);
        $cursor = $this->connection->collection($this->collection)->aggregate($pipeline);
        $rows   = [];

        foreach ($cursor as $doc) {
            $rows[] = $this->documentToArray($doc);
        }

        $this->connection->logOperation([
            'collection' => $this->collection,
            'operation'  => 'aggregate',
            'pipeline'   => $pipeline,
            'time'       => round((microtime(true) - $start) * 1000, 2),
        ]);

        return $rows;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
       * @psalm-mutation-free
     */
    private function buildOptions(): array
    {
        $options = [];

        if ($this->sort !== []) {
            $options['sort'] = $this->sort;
        }

        if ($this->limit !== null) {
            $options['limit'] = $this->limit;
        }

        if ($this->skip !== null) {
            $options['skip'] = $this->skip;
        }

        if ($this->projection !== []) {
            $options['projection'] = array_fill_keys($this->projection, 1);
        }

        return $options;
    }

    /**
     * Convert a MongoDB document (BSONDocument/array) to a plain PHP array.
     *
     * @param mixed $document
     * @return array<string, mixed>
       * @psalm-pure
     */
    private function documentToArray(mixed $document): array
    {
        if ($document instanceof \MongoDB\Model\BSONDocument) {
            return $document->getArrayCopy();
        }

        return (array) $document;
    }
}
