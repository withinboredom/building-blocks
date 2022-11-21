<?php

namespace Withinboredom\BuildingBlocks;

use Withinboredom\ResponseCode\HttpResponseCode;

/**
 * A ridiculously simple to reason about router.
 */
class Router
{
    protected readonly array $uriParts;

    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        protected array $routes = []
    ) {
        $this->uriParts = array_values(
            array_map($this->normalizePathPart(...), preg_split('@/@', $uri, -1, PREG_SPLIT_NO_EMPTY))
        );
    }

    public function registerRoute(string $method, string $path, callable $callback): void
    {
        // nothing to do if the method doesn't match
        if ($method !== $this->method) {
            return;
        }

        // break the path into parts of the path
        $route = preg_split('@/@', $path, -1, PREG_SPLIT_NO_EMPTY);

        // nothing to do if the number of parts don't match
        if (count($this->uriParts) !== count($route)) {
            return;
        }

        // now replace the parameters in the expected route with the actual values
        $matchRoute = array_map(
            static fn(string $v, string $k) => str_starts_with($v, ':') ? $k : $v,
            $route,
            $this->uriParts
        );

        // create a callback, deferring as much work as possible in exchange for some memory
        $callback = function () use ($route, $callback) {
            $params = array_filter(
                array_combine(
                    $route,
                    $this->uriParts,
                ),
                static fn(string|null $v, string $k) => !empty($k) && str_starts_with($k, ':'),
                ARRAY_FILTER_USE_BOTH
            );
            return $callback(
                ...
                array_combine(array_map(static fn($x) => trim($x, ':'), array_keys($params)), $params)
            );
        };

        // save the route
        $this->routes[] = [[$method, ...$matchRoute], $callback];
    }

    public function doRouting(): Result|null
    {
        // sort parameters
        //usort($this->routes, static fn($a, $b) => ($a[0] ?? null) <=> ($b[0] ?? null));

        $match = [$this->method, ...$this->uriParts];

        // find the route that matches the path
        foreach ($this->routes as $route) {
            if ($route[0] === $match) {
                return $route[1]();
            }
        }

        return new Result(HttpResponseCode::NotFound);
    }

    protected function normalizePathPart(string $part): string
    {
        return trim(rawurldecode($part));
    }
}
