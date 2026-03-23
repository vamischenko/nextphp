<?php

declare(strict_types=1);

namespace Nextphp\Routing\Tree;

use Nextphp\Routing\Route;

final readonly class MatchResult
{
    /**
     * @param array<string, string> $params
     */
    public function __construct(
        public Route $route,
        public array $params = [],
    ) {
    }
}
