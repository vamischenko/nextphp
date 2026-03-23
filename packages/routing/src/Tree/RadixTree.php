<?php

declare(strict_types=1);

namespace Nextphp\Routing\Tree;

use Nextphp\Routing\Route;

final class RadixTree
{
    private RadixNode $root;

    public function __construct()
    {
        $this->root = new RadixNode('/');
    }

    public function insert(string $method, string $path, Route $route): void
    {
        $method = strtoupper($method);

        // Root path stored directly on root node
        if ($path === '/' || $path === '') {
            $this->root->routes[$method] = $route;

            return;
        }

        $segments = $this->segmentize($path);
        $this->insertSegments($this->root, $segments, $method, $route);
    }

    /**
     * @return MatchResult|null  null = not found, check allowedMethods for 405
     */
    public function search(string $method, string $path): ?MatchResult
    {
        $method = strtoupper($method);

        // Root path
        if ($path === '/' || $path === '') {
            if (isset($this->root->routes[$method])) {
                return new MatchResult($this->root->routes[$method]);
            }

            return null;
        }

        $segments = $this->segmentize($path);
        $params = [];

        $node = $this->searchNode($this->root, $segments, $params);

        if ($node === null) {
            return null;
        }

        if (!isset($node->routes[$method])) {
            return null;
        }

        return new MatchResult($node->routes[$method], $params);
    }

    /**
     * Find allowed methods for a path (for 405 responses).
     *
     * @return string[]
     */
    public function allowedMethods(string $path): array
    {
        $segments = $this->segmentize($path);
        $params = [];

        $node = $this->searchNode($this->root, $segments, $params);

        if ($node === null) {
            return [];
        }

        return array_keys($node->routes);
    }

    /**
     * @return string[]
     */
    private function segmentize(string $path): array
    {
        $path = trim($path, '/');

        if ($path === '') {
            return [''];
        }

        return explode('/', $path);
    }

    /**
     * @param string[] $segments
     */
    private function insertSegments(RadixNode $node, array $segments, string $method, Route $route): void
    {
        if ($segments === []) {
            $node->routes[$method] = $route;

            return;
        }

        $segment = array_shift($segments);

        // Parameter segment: {id}
        if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
            $paramName = substr($segment, 1, -1);
            $child = $this->findOrCreateParamChild($node, $paramName);
            $this->insertSegments($child, $segments, $method, $route);

            return;
        }

        // Wildcard segment: *
        if ($segment === '*') {
            $child = $this->findOrCreateWildcardChild($node);
            $child->routes[$method] = $route;

            return;
        }

        // Static segment
        $child = $this->findOrCreateStaticChild($node, $segment);
        $this->insertSegments($child, $segments, $method, $route);
    }

    /**
     * @param string[]              $segments
     * @param array<string, string> $params
     */
    private function searchNode(RadixNode $node, array $segments, array &$params): ?RadixNode
    {
        if ($segments === []) {
            return $node->routes !== [] ? $node : null;
        }

        $segment = array_shift($segments);

        // Try static children first (higher priority)
        foreach ($node->children as $child) {
            if (!$child->isParam && !$child->isWildcard && $child->prefix === $segment) {
                $result = $this->searchNode($child, $segments, $params);

                if ($result !== null) {
                    return $result;
                }
            }
        }

        // Try param children
        foreach ($node->children as $child) {
            if ($child->isParam) {
                $savedParams = $params;
                $params[$child->paramName] = $segment;
                $result = $this->searchNode($child, $segments, $params);

                if ($result !== null) {
                    return $result;
                }

                $params = $savedParams;
            }
        }

        // Try wildcard children
        foreach ($node->children as $child) {
            if ($child->isWildcard && $child->routes !== []) {
                return $child;
            }
        }

        return null;
    }

    private function findOrCreateStaticChild(RadixNode $node, string $segment): RadixNode
    {
        foreach ($node->children as $child) {
            if (!$child->isParam && !$child->isWildcard && $child->prefix === $segment) {
                return $child;
            }
        }

        $child = new RadixNode($segment);
        $node->children[] = $child;

        return $child;
    }

    private function findOrCreateParamChild(RadixNode $node, string $paramName): RadixNode
    {
        foreach ($node->children as $child) {
            if ($child->isParam && $child->paramName === $paramName) {
                return $child;
            }
        }

        $child = new RadixNode('{' . $paramName . '}');
        $child->isParam = true;
        $child->paramName = $paramName;
        $node->children[] = $child;

        return $child;
    }

    private function findOrCreateWildcardChild(RadixNode $node): RadixNode
    {
        foreach ($node->children as $child) {
            if ($child->isWildcard) {
                return $child;
            }
        }

        $child = new RadixNode('*');
        $child->isWildcard = true;
        $node->children[] = $child;

        return $child;
    }
}
