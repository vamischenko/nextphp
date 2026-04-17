<?php

declare(strict_types=1);

namespace Nextphp\Orm\Model\Relations;

use Nextphp\Orm\Model\Model;
use Nextphp\Orm\Query\Builder;

abstract class Relation
{
    protected Builder $query;

    /**
     * @param class-string<Model> $related
     */
    public function __construct(
        protected readonly Model $parent,
        protected readonly string $related,
    ) {
        /** @var Model $instance */
        $instance = new $related();
        $this->query = $instance->newQuery();
    }

    /**
     * Get results of the relation.
     *
     * @return Model|Model[]|null
     */
    /**
     * @psalm-impure
     */
    abstract public function getResults(): mixed;

    /**
     * Add constraints to the relation query based on the parent model.
     */
    /**
     * @psalm-impure
     */
    abstract public function addConstraints(): void;

    /**
      * @psalm-mutation-free
     */
    protected function relatedModel(): Model
    {
        return new $this->related();
    }
}
