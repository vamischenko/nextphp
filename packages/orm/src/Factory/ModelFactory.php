<?php

declare(strict_types=1);

namespace Nextphp\Orm\Factory;

use Nextphp\Orm\Model\Model;

/**
 * Base class for model factories.
 *
 * Usage:
 *
 *   class UserFactory extends ModelFactory
 *   {
 *       protected string $model = User::class;
 *
 *       public function definition(): array
 *       {
 *           return [
 *               'name'  => $this->faker->name(),
 *               'email' => $this->faker->email(),
 *           ];
 *       }
 *
 *       public function unverified(): static
 *       {
 *           return $this->state(['email_verified_at' => null]);
 *       }
 *   }
 *
 *   // Create 3 users with a specific state
 *   $users = UserFactory::new()->count(3)->state(['role' => 'admin'])->make();
 *
 * @template TModel of Model
 */
abstract class ModelFactory
{
    /** @var class-string<TModel> */
    protected string $model;

    protected FakerGenerator $faker;

    /** @var array<string, mixed> */
    private array $stateOverrides = [];

    private int $count = 1;

    /** @var list<callable(array<string,mixed>, ?TModel): array<string,mixed>> */
    private array $afterMaking = [];

    /** @var list<callable(TModel): void> */
    private array $afterCreating = [];

    final public function __construct()
    {
        $this->faker = new FakerGenerator();
    }

    // -------------------------------------------------------------------------
    // Entry points
    // -------------------------------------------------------------------------

    public static function new(): static
    {
        return new static();
    }

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    public function count(int $count): static
    {
        $clone        = clone $this;
        $clone->count = $count;
        return $clone;
    }

    /**
     * Merge additional attributes into the definition.
     *
     * @param array<string, mixed> $attributes
     */
    public function state(array $attributes): static
    {
        $clone                 = clone $this;
        $clone->stateOverrides = array_merge($clone->stateOverrides, $attributes);
        return $clone;
    }

    /**
     * Run a callback after making each model (without persisting).
     *
     * @param callable(array<string,mixed>): array<string,mixed> $callback
     */
    public function afterMaking(callable $callback): static
    {
        $clone                = clone $this;
        $clone->afterMaking[] = $callback;
        return $clone;
    }

    /**
     * Run a callback after creating (persisting) each model.
     *
     * @param callable(TModel): void $callback
     */
    public function afterCreating(callable $callback): static
    {
        $clone                  = clone $this;
        $clone->afterCreating[] = $callback;
        return $clone;
    }

    // -------------------------------------------------------------------------
    // Definition (subclasses must implement)
    // -------------------------------------------------------------------------

    /**
     * Return the base attribute definition for a single model.
     *
     * @return array<string, mixed>
     */
    abstract public function definition(): array;

    // -------------------------------------------------------------------------
    // Make (without persisting)
    // -------------------------------------------------------------------------

    /**
     * Build attribute array(s) without saving to DB.
     *
     * @param array<string, mixed> $override
     * @return ($this is static ? (int is 1 ? array<string, mixed> : list<array<string, mixed>>) : never)
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function makeRaw(array $override = []): array
    {
        if ($this->count === 1) {
            return $this->buildAttributes($override);
        }

        $result = [];
        for ($i = 0; $i < $this->count; $i++) {
            $result[] = $this->buildAttributes($override);
        }
        return $result;
    }

    /**
     * Build model instance(s) without saving.
     *
     * @param array<string, mixed> $override
     * @return TModel|list<TModel>
     */
    public function make(array $override = []): Model|array
    {
        if ($this->count === 1) {
            return $this->makeOne($override);
        }

        $result = [];
        for ($i = 0; $i < $this->count; $i++) {
            $result[] = $this->makeOne($override);
        }
        return $result;
    }

    // -------------------------------------------------------------------------
    // Create (persist to DB)
    // -------------------------------------------------------------------------

    /**
     * Create and persist model instance(s).
     *
     * @param array<string, mixed> $override
     * @return TModel|list<TModel>
     */
    public function create(array $override = []): Model|array
    {
        if ($this->count === 1) {
            return $this->createOne($override);
        }

        $result = [];
        for ($i = 0; $i < $this->count; $i++) {
            $result[] = $this->createOne($override);
        }
        return $result;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function buildAttributes(array $override): array
    {
        $attrs = array_merge($this->definition(), $this->stateOverrides, $override);

        foreach ($this->afterMaking as $callback) {
            $attrs = $callback($attrs);
        }

        return $attrs;
    }

    /**
     * @param array<string, mixed> $override
     * @return TModel
     */
    private function makeOne(array $override): Model
    {
        $attrs = $this->buildAttributes($override);
        /** @var TModel $model */
        $model = new $this->model();
        $model->forceFill($attrs);
        return $model;
    }

    /**
     * @param array<string, mixed> $override
     * @return TModel
     */
    private function createOne(array $override): Model
    {
        $model = $this->makeOne($override);
        $model->save();

        foreach ($this->afterCreating as $callback) {
            $callback($model);
        }

        return $model;
    }
}
