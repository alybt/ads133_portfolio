<?php


namespace App;

class Router{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes[$path] = $handler;
    }

    public function dispatch(string $uri): void
    {
        $uri = rtrim($uri, '/') ?: '/';

        if (!isset($this->routes[$uri])) {
            http_response_code(404);
            
            return;
        }

        call_user_func($this->routes[$uri]);
    }
}