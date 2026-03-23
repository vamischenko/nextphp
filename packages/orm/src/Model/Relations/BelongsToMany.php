<?php

declare(strict_types=1);

namespace Nextphp\Orm\Model\Relations;

use Nextphp\Orm\Model\Model;

final class BelongsToMany extends Relation
{
    public function __construct(
        Model $parent,
        string $related,
        private readonly string $pivotTable,
        private readonly string $foreignPivotKey,
        private readonly string $relatedPivotKey,
        private readonly string $parentKey,
        private readonly string $relatedKey,
    ) {
        parent::__construct($parent, $related);
        $this->addConstraints();
    }

    public function addConstraints(): void
    {
        $instance = $this->relatedModel();
        $relatedTable = $instance->getTable();

        $this->query
            ->join(
                $this->pivotTable,
                $relatedTable . '.' . $this->relatedKey,
                '=',
                $this->pivotTable . '.' . $this->relatedPivotKey,
            )
            ->where(
                $this->pivotTable . '.' . $this->foreignPivotKey,
                '=',
                $this->parent->getAttribute($this->parentKey),
            );
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

    /**
     * Attach related models via pivot table.
     *
     * @param int[]|string[] $ids
     */
    public function attach(array $ids): void
    {
        $connection = $this->relatedModel()->getConnection();
        $parentId = $this->parent->getAttribute($this->parentKey);

        foreach ($ids as $id) {
            $connection->insert(
                sprintf(
                    'INSERT INTO %s (%s, %s) VALUES (?, ?)',
                    $this->pivotTable,
                    $this->foreignPivotKey,
                    $this->relatedPivotKey,
                ),
                [$parentId, $id],
            );
        }
    }

    /**
     * Detach related models from pivot table.
     *
     * @param int[]|string[] $ids
     */
    public function detach(array $ids = []): void
    {
        $connection = $this->relatedModel()->getConnection();
        $parentId = $this->parent->getAttribute($this->parentKey);

        if ($ids === []) {
            $connection->affectingStatement(
                sprintf('DELETE FROM %s WHERE %s = ?', $this->pivotTable, $this->foreignPivotKey),
                [$parentId],
            );

            return;
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $connection->affectingStatement(
            sprintf(
                'DELETE FROM %s WHERE %s = ? AND %s IN (%s)',
                $this->pivotTable,
                $this->foreignPivotKey,
                $this->relatedPivotKey,
                $placeholders,
            ),
            array_merge([$parentId], $ids),
        );
    }
}
