<?php


namespace App\Router;
use RuntimeException;


class Router{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes[$path] = $handler;
    }

    public function add(string $method, string $path, callable|array $handler): void
    {
        $method = strtoupper($method);
        $path   = $this->normalize($path);

        if (isset($this->routes[$method][$path])) {
            throw new RuntimeException("Duplicate route: {$method} {$path}");
        }

        $this->routes[$method][$path] = $handler;
    }

    // GET
    public function onGet(string $path, callable|array $handler): void    { $this->add('GET', $path, $handler); }
    public function onPost(string $path, callable|array $handler): void   { $this->add('POST', $path, $handler); }
    public function onPut(string $path, callable|array $handler): void    { $this->add('PUT', $path, $handler); }
    public function onDelete(string $path, callable|array $handler): void { $this->add('DELETE', $path, $handler); }

    public function dispatch(string $uri): void
    {
        $uri = rtrim($uri, '/') ?: '/';

        if (!isset($this->routes[$uri])) {
            http_response_code(404);
            
            return;
        }

        call_user_func($this->routes[$uri]);
    }

    private function invoke(callable|array $handler): void
    {
        // [Controller::class, 'method']
        if (is_array($handler) && is_string($handler[0] ?? null)) {
            [$class, $action] = $handler;
            if (!class_exists($class)) {
                throw new RuntimeException("Controller not found: {$class}");
            }
            $controller = new $class();
            if (!method_exists($controller, $action)) {
                throw new RuntimeException("Action not found: {$class}::{$action}");
            }
            $controller->$action();
            return;
        }

        // plain closure
        if (is_callable($handler)) {
            $handler();
            return;
        }

        throw new RuntimeException('Invalid route handler.');
    }

    private function normalize(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

}