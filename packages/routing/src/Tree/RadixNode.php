<?php

declare(strict_types=1);

namespace Nextphp\Routing\Tree;

use Nextphp\Routing\Route;

final class RadixNode
{
    public string $prefix;

    /** @var self[] */
    public array $children = [];

    /** @var array<string, Route> HTTP method => Route */
    public array $routes = [];

    public bool $isParam = false;

    public string $paramName = '';

    public bool $isWildcard = false;

    public function __construct(string $prefix = '')
    {
        $this->prefix = $prefix;
    }
}
