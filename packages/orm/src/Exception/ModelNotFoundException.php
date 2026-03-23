<?php

declare(strict_types=1);

namespace Nextphp\Orm\Exception;

final class ModelNotFoundException extends OrmException
{
    public function __construct(string $model, int|string $id)
    {
        parent::__construct(sprintf('No query results for model [%s] with id [%s].', $model, $id));
    }
}
