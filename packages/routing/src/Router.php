<?php

declare(strict_types=1);

namespace Nextphp\Routing;

use Nextphp\Routing\Exception\MethodNotAllowedException;
use Nextphp\Routing\Exception\RouteNotFoundException;
use Nextphp\Routing\Tree\MatchResult;
use Nextphp\Routing\Tree\RadixTree;

final class Router
{
    private RadixTree $tree;

    private RouteCollection $collection;

    public function __construct()
    {
        $this->tree = new RadixTree();
        $this->collection = new RouteCollection();
    }

    public function get(string $path, mixed $handler): Route
    {
        return $this->add(['GET', 'HEAD'], $path, $handler);
    }

    public function post(string $path, mixed $handler): Route
    {
        return $this->add(['POST'], $path, $handler);
    }

    public function put(string $path, mixed $handler): Route
    {
        return $this->add(['PUT'], $path, $handler);
    }

    public function patch(string $path, mixed $handler): Route
    {
        return $this->add(['PATCH'], $path, $handler);
    }

    public function delete(string $path, mixed $handler): Route
    {
        return $this->add(['DELETE'], $path, $handler);
    }

    public function options(string $path, mixed $handler): Route
    {
        return $this->add(['OPTIONS'], $path, $handler);
    }

    /**
     * @param string[] $methods
     */
    public function match(array $methods, string $path, mixed $handler): Route
    {
        return $this->add($methods, $path, $handler);
    }

    public function any(string $path, mixed $handler): Route
    {
        return $this->add(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $path, $handler);
    }

    public function group(string $prefix, \Closure $callback): void
    {
        $group = new RouteGroup($this, $prefix);
        $callback($group);
    }

    /**
     * Register RESTful resource routes.
     *
     * GET    /resource          → index
     * GET    /resource/{id}     → show
     * POST   /resource          → store
     * PUT    /resource/{id}     → update
     * PATCH  /resource/{id}     → partialUpdate
     * DELETE /resource/{id}     → destroy
     */
    public function resource(string $path, string $controller): void
    {
        $path = '/' . ltrim($path, '/');
        $idPath = rtrim($path, '/') . '/{id}';

        $this->get($path, [$controller, 'index'])->named(
            $this->resourceName($path, 'index'),
        );
        $this->post($path, [$controller, 'store'])->named(
            $this->resourceName($path, 'store'),
        );
        $this->get($idPath, [$controller, 'show'])->named(
            $this->resourceName($path, 'show'),
        );
        $this->put($idPath, [$controller, 'update'])->named(
            $this->resourceName($path, 'update'),
        );
        $this->patch($idPath, [$controller, 'partialUpdate'])->named(
            $this->resourceName($path, 'partialUpdate'),
        );
        $this->delete($idPath, [$controller, 'destroy'])->named(
            $this->resourceName($path, 'destroy'),
        );
    }

    /**
     * Add an already-built Route to the router (used by RouteGroup).
     */
    public function addRoute(Route $route): void
    {
        foreach ($route->getMethods() as $method) {
            $this->tree->insert($method, $route->getPath(), $route);
        }

        $this->collection->add($route);
    }

    /**
     * Dispatch: find the matching route or throw.
     *
     * @throws RouteNotFoundException
     * @throws MethodNotAllowedException
     */
    public function dispatch(string $method, string $path): MatchResult
    {
        $result = $this->tree->search($method, $path);

        if ($result !== null) {
            return $result;
        }

        $allowed = $this->tree->allowedMethods($path);

        if ($allowed !== []) {
            throw new MethodNotAllowedException($allowed, $method, $path);
        }

        throw new RouteNotFoundException($method, $path);
    }

    public function getCollection(): RouteCollection
    {
        return $this->collection;
    }

    public function getUrlGenerator(): UrlGenerator
    {
        return new UrlGenerator($this->collection);
    }

    /**
     * @param string[] $methods
     */
    private function add(array $methods, string $path, mixed $handler): Route
    {
        $path = '/' . ltrim($path, '/');
        $route = new Route($methods, $path, $handler);

        $this->addRoute($route);

        return $route;
    }

    private function resourceName(string $path, string $action): string
    {
        $name = trim($path, '/');
        $name = str_replace('/', '.', $name);

        return $name . '.' . $action;
    }
}
