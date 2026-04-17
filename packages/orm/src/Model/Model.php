<?php

declare(strict_types=1);

namespace Nextphp\Orm\Model;

use Nextphp\Orm\Connection\ConnectionInterface;
use Nextphp\Orm\Exception\ModelNotFoundException;
use Nextphp\Orm\Exception\OrmException;
use Nextphp\Orm\Model\Relations\BelongsTo;
use Nextphp\Orm\Model\Relations\BelongsToMany;
use Nextphp\Orm\Model\Relations\HasMany;
use Nextphp\Orm\Model\Relations\HasOne;
use Nextphp\Orm\Model\Relations\MorphTo;
use Nextphp\Orm\Query\Builder;

abstract class Model
{
    protected string $table = '';

    protected string $primaryKey = 'id';

    protected bool $autoIncrement = true;

    /** @var string[] */
    protected array $fillable = [];

    /** @var string[] */
    protected array $guarded = ['*'];

    protected bool $timestamps = true;

    protected string $createdAt = 'created_at';

    protected string $updatedAt = 'updated_at';

    protected bool $softDelete = false;

    protected string $deletedAt = 'deleted_at';

    /** @var array<string, mixed> */
    protected array $attributes = [];

    /** @var array<string, mixed> */
    protected array $original = [];

    /** @var array<string, mixed> */
    protected array $relations = [];

    protected bool $exists = false;

    /** @var array<string, callable[]> */
    private static array $listeners = [];

    /** @var array<class-string<static>, array<int, callable(Builder): void>> */
    private static array $globalScopes = [];

    private static bool $preventLazyLoading = false;

    protected static bool $warnOnLazyLoading = false;

    /** @var null|callable(Model, string): void */
    protected static $lazyLoadingWarningHandler = null;

    private static ?ConnectionInterface $defaultConnection = null;

    private ?ConnectionInterface $connection = null;

    // -------------------------------------------------------------------------
    // Static connection management
    // -------------------------------------------------------------------------

    /**
      * @psalm-external-mutation-free
     */
    public static function setDefaultConnection(ConnectionInterface $connection): void
    {
        static::$defaultConnection = $connection;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function getConnection(): ConnectionInterface
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        if (static::$defaultConnection !== null) {
            return static::$defaultConnection;
        }

        throw new \RuntimeException('No database connection configured for model ' . static::class);
    }

    /**
      * @psalm-external-mutation-free
     */
    public function setConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
    }

    // -------------------------------------------------------------------------
    // Table / key helpers
    // -------------------------------------------------------------------------

    /**
      * @psalm-mutation-free
     */
    public function getTable(): string
    {
        if ($this->table !== '') {
            return $this->table;
        }

        // Auto-derive table name from class name (e.g. User -> users)
        $class = (new \ReflectionClass($this))->getShortName();

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class) ?? $class) . 's';
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
      * @psalm-mutation-free
     */
    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    // -------------------------------------------------------------------------
    // Attribute access
    // -------------------------------------------------------------------------

    /**
      * @psalm-mutation-free
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>
       * @psalm-mutation-free
     */
    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (! array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
      * @psalm-mutation-free
     */
    public function isDirty(string $key = ''): bool
    {
        $dirty = $this->getDirty();

        if ($key === '') {
            return $dirty !== [];
        }

        return array_key_exists($key, $dirty);
    }

    public function __get(string $key): mixed
    {
        // Check loaded relations first
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        // Check if it's a relation method
        if (method_exists($this, $key)) {
            if (static::$preventLazyLoading) {
                throw new OrmException(sprintf('Lazy loading violation on relation "%s" for model %s', $key, static::class));
            }

            if (self::$warnOnLazyLoading) {
                $this->warnAboutLazyLoading($key);
            }
            $relation = $this->$key();

            if ($relation instanceof Relations\Relation || $relation instanceof MorphTo) {
                /** @var mixed $result */
                $result = $relation->getResults();
                $this->relations[$key] = $result;

                return $result;
            }
        }

        return $this->attributes[$key] ?? null;
    }

    /**
      * @psalm-external-mutation-free
     */
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
      * @psalm-mutation-free
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]) || isset($this->relations[$key]);
    }

    // -------------------------------------------------------------------------
    // Fill
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $attributes
       * @psalm-external-mutation-free
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $attributes
       * @psalm-external-mutation-free
     */
    public function forceFill(array $attributes): static
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
      * @psalm-mutation-free
     */
    private function isFillable(string $key): bool
    {
        if ($this->fillable !== []) {
            return in_array($key, $this->fillable, true);
        }

        if ($this->guarded === ['*']) {
            return false;
        }

        return ! in_array($key, $this->guarded, true);
    }

    // -------------------------------------------------------------------------
    // Query Builder
    // -------------------------------------------------------------------------

    public function newQuery(): Builder
    {
        $query = (new Builder($this->getConnection()))->table($this->getTable());

        if ($this->softDelete && $query->shouldApplySoftDeleteScope()) {
            $query->whereNull($this->deletedAt);
        }

        foreach (static::$globalScopes[static::class] ?? [] as $scope) {
            $scope($query);
        }

        return $query;
    }

    public static function query(): Builder
    {
        return (new static())->newQuery();
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $attributes
     */
    public static function create(array $attributes): static
    {
        $model = new static();
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function forceCreate(array $attributes): static
    {
        $model = new static();
        $model->forceFill($attributes);
        $model->save();

        return $model;
    }

    public function save(): bool
    {
        $this->fireEvent(ModelEvent::Saving);

        if ($this->exists) {
            $result = $this->performUpdate();
        } else {
            $result = $this->performInsert();
        }

        if ($result) {
            $this->original = $this->attributes;
            $this->fireEvent(ModelEvent::Saved);
        }

        return $result;
    }

    public function delete(): bool
    {
        $this->fireEvent(ModelEvent::Deleting);

        if ($this->softDelete) {
            $this->attributes[$this->deletedAt] = date('Y-m-d H:i:s');
            $result = $this->performUpdate();
        } else {
            $result = $this->newQuery()
                ->where($this->primaryKey, '=', $this->getKey())
                ->delete() > 0;
        }

        if ($result) {
            $this->exists = false;
            $this->fireEvent(ModelEvent::Deleted);
        }

        return $result;
    }

    public function restore(): bool
    {
        if (! $this->softDelete) {
            return false;
        }

        $this->fireEvent(ModelEvent::Restoring);
        $this->attributes[$this->deletedAt] = null;
        $result = $this->performUpdate();

        if ($result) {
            $this->exists = true;
            $this->fireEvent(ModelEvent::Restored);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);

        return $this->save();
    }

    // -------------------------------------------------------------------------
    // Find / all
    // -------------------------------------------------------------------------

    public static function find(int|string $id): ?static
    {
        $model = new static();
        $row = $model->newQuery()->where($model->primaryKey, '=', $id)->first();

        if ($row === null) {
            return null;
        }

        return $model->newFromArray($row);
    }

    public static function findOrFail(int|string $id): static
    {
        $result = static::find($id);

        if ($result === null) {
            throw new ModelNotFoundException(static::class, $id);
        }

        return $result;
    }

    /**
     * @return static[]
     */
    public static function all(): array
    {
        $model = new static();
        $rows = $model->newQuery()->get();

        return array_map(fn ($row) => $model->newFromArray($row), $rows);
    }

    /**
     * @param string[]|string $relations
     * @return static[]
     */
    public static function with(array|string $relations): array
    {
        $models = static::all();
        $rels = is_array($relations) ? $relations : [$relations];
        foreach ($models as $model) {
            foreach ($rels as $relation) {
                $model->$relation;
            }
        }

        return $models;
    }

    public static function withTrashed(): Builder
    {
        $model = new static();
        $query = (new Builder($model->getConnection()))
            ->table($model->getTable())
            ->withTrashed();

        foreach (static::$globalScopes[static::class] ?? [] as $scope) {
            $scope($query);
        }

        return $query;
    }

    public static function onlyTrashed(): Builder
    {
        $model = new static();
        $query = (new Builder($model->getConnection()))
            ->table($model->getTable())
            ->onlyTrashed($model->deletedAt);

        foreach (static::$globalScopes[static::class] ?? [] as $scope) {
            $scope($query);
        }

        return $query;
    }

    /**
      * @psalm-external-mutation-free
     */
    public static function addGlobalScope(callable $scope): void
    {
        static::$globalScopes[static::class][] = $scope;
    }

    /**
      * @psalm-external-mutation-free
     */
    public static function preventLazyLoading(bool $state = true): void
    {
        static::$preventLazyLoading = $state;
    }

    /**
      * @psalm-external-mutation-free
     */
    public static function warnOnLazyLoading(bool $state = true): void
    {
        self::$warnOnLazyLoading = $state;
    }

    /**
     * @param null|callable(Model, string): void $handler
       * @psalm-external-mutation-free
     */
    public static function setLazyLoadingWarningHandler(?callable $handler): void
    {
        self::$lazyLoadingWarningHandler = $handler;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function newFromArray(array $row): static
    {
        $model = new static();
        $model->forceFill($row);
        $model->original = $row;
        $model->exists = true;

        return $model;
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Apply a scope closure to a fresh builder.
     */
    public static function withScope(\Closure $scope): Builder
    {
        $query = static::query();
        $scope($query);

        return $query;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * @param class-string<Model> $related
     */
    protected function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        $instance = new $related();
        $foreignKey ??= $this->getForeignKeyName();
        $localKey ??= $this->primaryKey;

        return new HasOne($this, $related, $foreignKey, $localKey);
    }

    /**
     * @param class-string<Model> $related
     */
    protected function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $instance = new $related();
        $foreignKey ??= $this->getForeignKeyName();
        $localKey ??= $this->primaryKey;

        return new HasMany($this, $related, $foreignKey, $localKey);
    }

    /**
     * @param class-string<Model> $related
     */
    protected function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $instance = new $related();
        $ownerKey ??= $instance->getPrimaryKey();
        $foreignKey ??= $this->snakeCase((new \ReflectionClass($related))->getShortName()) . '_id';

        return new BelongsTo($this, $related, $foreignKey, $ownerKey);
    }

    /**
     * @param class-string<Model> $related
     */
    protected function belongsToMany(
        string $related,
        ?string $pivotTable = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?string $parentKey = null,
        ?string $relatedKey = null,
    ): BelongsToMany {
        $instance = new $related();
        $parentKey ??= $this->primaryKey;
        $relatedKey ??= $instance->getPrimaryKey();

        // Auto-derive pivot table name (alphabetical order of singular table names)
        if ($pivotTable === null) {
            $tables = [
                $this->snakeCase((new \ReflectionClass($this))->getShortName()),
                $this->snakeCase((new \ReflectionClass($related))->getShortName()),
            ];
            sort($tables);
            $pivotTable = implode('_', $tables);
        }

        $foreignPivotKey ??= $this->getForeignKeyName();
        $relatedPivotKey ??= $this->snakeCase((new \ReflectionClass($related))->getShortName()) . '_id';

        return new BelongsToMany($this, $related, $pivotTable, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey);
    }

    /**
     * @param array<string, class-string<Model>> $typeMap
       * @psalm-mutation-free
     */
    protected function morphTo(
        ?string $name = null,
        ?string $typeColumn = null,
        ?string $idColumn = null,
        array $typeMap = [],
    ): MorphTo {
        $relationName = $name ?? $this->inferCallerMethod();
        $typeColumn ??= $relationName . '_type';
        $idColumn ??= $relationName . '_id';

        return new MorphTo($this, $typeColumn, $idColumn, $typeMap);
    }

    // -------------------------------------------------------------------------
    // Model Events
    // -------------------------------------------------------------------------

    /**
      * @psalm-external-mutation-free
     */
    public static function on(ModelEvent $event, callable $listener): void
    {
        static::$listeners[static::class . ':' . $event->value][] = $listener;
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function observe(object|string $observer): void
    {
        $instance = is_string($observer) ? new $observer() : $observer;

        if (method_exists($instance, 'creating')) {
            static::on(ModelEvent::Creating, static fn (Model $m): mixed => $instance->creating($m));
        }
        if (method_exists($instance, 'created')) {
            static::on(ModelEvent::Created, static fn (Model $m): mixed => $instance->created($m));
        }
        if (method_exists($instance, 'updating')) {
            static::on(ModelEvent::Updating, static fn (Model $m): mixed => $instance->updating($m));
        }
        if (method_exists($instance, 'updated')) {
            static::on(ModelEvent::Updated, static fn (Model $m): mixed => $instance->updated($m));
        }
        if (method_exists($instance, 'saving')) {
            static::on(ModelEvent::Saving, static fn (Model $m): mixed => $instance->saving($m));
        }
        if (method_exists($instance, 'saved')) {
            static::on(ModelEvent::Saved, static fn (Model $m): mixed => $instance->saved($m));
        }
        if (method_exists($instance, 'deleting')) {
            static::on(ModelEvent::Deleting, static fn (Model $m): mixed => $instance->deleting($m));
        }
        if (method_exists($instance, 'deleted')) {
            static::on(ModelEvent::Deleted, static fn (Model $m): mixed => $instance->deleted($m));
        }
        if (method_exists($instance, 'restoring')) {
            static::on(ModelEvent::Restoring, static fn (Model $m): mixed => $instance->restoring($m));
        }
        if (method_exists($instance, 'restored')) {
            static::on(ModelEvent::Restored, static fn (Model $m): mixed => $instance->restored($m));
        }
    }

    private function fireEvent(ModelEvent $event): void
    {
        $key = static::class . ':' . $event->value;

        foreach (static::$listeners[$key] ?? [] as $listener) {
            $listener($this);
        }
    }

    // -------------------------------------------------------------------------
    // Internal persistence
    // -------------------------------------------------------------------------

    private function performInsert(): bool
    {
        $this->fireEvent(ModelEvent::Creating);

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $this->attributes[$this->createdAt] = $now;
            $this->attributes[$this->updatedAt] = $now;
        }

        $insertData = $this->attributes;

        if ($this->autoIncrement) {
            unset($insertData[$this->primaryKey]);
        }

        $id = $this->newQuery()->insert($insertData);

        if ($id !== false && $this->autoIncrement) {
            $this->attributes[$this->primaryKey] = $id;
        }

        $this->exists = true;
        $this->fireEvent(ModelEvent::Created);

        return true;
    }

    private function performUpdate(): bool
    {
        $dirty = $this->getDirty();

        if ($dirty === []) {
            return true;
        }

        $this->fireEvent(ModelEvent::Updating);

        if ($this->timestamps && ! array_key_exists($this->updatedAt, $dirty)) {
            $this->attributes[$this->updatedAt] = date('Y-m-d H:i:s');
            $dirty[$this->updatedAt] = $this->attributes[$this->updatedAt];
        }

        $affected = $this->newQuery()
            ->where($this->primaryKey, '=', $this->getKey())
            ->update($dirty);

        if ($affected > 0) {
            $this->fireEvent(ModelEvent::Updated);
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
      * @psalm-mutation-free
     */
    private function getForeignKeyName(): string
    {
        return $this->snakeCase((new \ReflectionClass($this))->getShortName()) . '_id';
    }

    /**
      * @psalm-pure
     */
    private function snakeCase(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value) ?? $value);
    }

    private function warnAboutLazyLoading(string $relation): void
    {
        if (is_callable(static::$lazyLoadingWarningHandler)) {
            $handler = static::$lazyLoadingWarningHandler;
            $handler($this, $relation);

            return;
        }

        @trigger_error(
            sprintf('N+1 warning: lazy loaded relation "%s" on model %s', $relation, static::class),
            E_USER_WARNING,
        );
    }

    /**
      * @psalm-pure
     */
    private function inferCallerMethod(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[2]['function'] ?? $trace[1]['function'] ?? '';

        return $caller !== '' ? $caller : 'morph';
    }
}
