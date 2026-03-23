<?php

declare(strict_types=1);

namespace Nextphp\Orm\Model\Relations;

use Nextphp\Orm\Model\Model;

final class HasMany extends Relation
{
    public function __construct(
        Model $parent,
        string $related,
        private readonly string $foreignKey,
        private readonly string $localKey,
    ) {
        parent::__construct($parent, $related);
        $this->addConstraints();
    }

    public function addConstraints(): void
    {
        $this->query->where($this->foreignKey, '=', $this->parent->getAttribute($this->localKey));
    }

    /**
     * @return Model[]
     */
    public function getResults(): array
    {
        $rows = $this->query->get();
        $instance = $this->relatedModel();

        return array_map(fn ($row) => $instance->newFromArray($row), $rows);
    }
}
