<?php

declare(strict_types=1);

namespace Nextphp\Orm\Model\Relations;

use Nextphp\Orm\Model\Model;

final class MorphTo
{
    /**
     * @param array<string, class-string<Model>> $typeMap
       * @psalm-mutation-free
     */
    public function __construct(
        private readonly Model $parent,
        private readonly string $typeColumn,
        private readonly string $idColumn,
        private readonly array $typeMap = [],
    ) {
    }

    public function getResults(): ?Model
    {
        $type = $this->parent->getAttribute($this->typeColumn);
        $id = $this->parent->getAttribute($this->idColumn);

        if (! is_string($type) || $type === '' || $id === null) {
            return null;
        }

        $class = $this->typeMap[$type] ?? $type;

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $class */
        if (is_int($id) || is_string($id)) {
            return $class::find($id);
        }

        return null;
    }
}
