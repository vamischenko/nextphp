<?php

declare(strict_types=1);

namespace Nextphp\Orm\Model\Relations;

use Nextphp\Orm\Model\Model;

final class BelongsTo extends Relation
{
    public function __construct(
        Model $parent,
        string $related,
        private readonly string $foreignKey,
        private readonly string $ownerKey,
    ) {
        parent::__construct($parent, $related);
        $this->addConstraints();
    }

    public function addConstraints(): void
    {
        $this->query->where($this->ownerKey, '=', $this->parent->getAttribute($this->foreignKey));
    }

    public function getResults(): ?Model
    {
        $row = $this->query->first();

        if ($row === null) {
            return null;
        }

        return $this->relatedModel()->newFromArray($row);
    }
}
