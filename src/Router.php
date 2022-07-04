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
        $this->uriParts = array_values(array_map($this->normalizePathPart(...), array_filter(explode('/', $uri))));
    }

    public function registerRoute(string $method, string $path, callable $callback): void
    {
        // break the path into parts of the path
        $route = array_values(array_filter(explode('/', $path)));

        // normalize both arrays
        $max = max(count($this->uriParts), count($route));

        // combine the array into a single array with the parameters as keys
        $combined = array_combine(
            array_pad($route, $max, null),
            array_pad($this->uriParts, $max, null)
        );

        // calculate expected parameters and their current values
        $params = array_filter(
            array_map(static fn(string $k, string|null $v) => $k === $v || empty($k) ? null : $v,
                array_keys($combined),
                $combined)
        );

        // now replace the parameters in the expected route with the actual values
        $matchRoute = array_replace($route, $params);

        // create a callback, deferring as much work as possible in exchange for some memory
        $callback = static function () use ($callback, $combined) {
            $params = array_filter(
                $combined,
                static fn(string $k, string|null $v) => $k !== $v && !empty($k),
                ARRAY_FILTER_USE_BOTH
            );
            return $callback(...array_combine(array_map(static fn($x) => trim($x, ':'), array_keys($params)), $params));
        };

        // save the route
        $this->routes[] = [[$method, ...$matchRoute], $callback];
    }

    public function doRouting(): Result|null
    {
        // calculate the maximum number of segments in a path
        $maxMatch = array_reduce(
            array_column($this->routes, 0),
            static fn($carry, $item) => max($carry, count($item)),
            0
        );

        // sort parameters
        usort($this->routes, static fn($a, $b) => ($a[0] ?? null) <=> ($b[0] ?? null));

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