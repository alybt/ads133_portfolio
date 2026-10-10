<?php


namespace App\Router;
use RuntimeException;


class Router{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
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

    public function dispatch(?Request $request = null): void
    {
        $request ??= Request::capture();
        $method = $request->method;
        $path   = $this->normalize($request->path);

        // 1. Direct match
        if (isset($this->routes[$method][$path])) {
            $this->invoke($this->routes[$method][$path], $request);
            return;
        }

        // 2. Pattern match (e.g. /users/{id})
        if (!empty($this->routes[$method])) {
            foreach ($this->routes[$method] as $routePath => $handler) {
                if (str_contains($routePath, '{')) {
                    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);
                    $pattern = '#^' . $pattern . '$#';
                    if (preg_match($pattern, $path, $matches)) {
                        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                        $request = $request->withParams($params);
                        $this->invoke($handler, $request);
                        return;
                    }
                }
            }
        }

        http_response_code(404);
        if (is_file(VIEW_PATH . '/pages/404.php')) {
            view('pages/404', ['pageTitle' => '404 Not Found']);
        } elseif (is_file(BASE_PATH . '/public/pages/404.php')) {
            require BASE_PATH . '/public/pages/404.php';
        } else {
            echo "404 Not Found";
        }
    }

    private function invoke(callable|array $handler, Request $request): void
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
            $controller->$action($request);
            return;
        }

        // plain closure
        if (is_callable($handler)) {
            $handler($request);
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