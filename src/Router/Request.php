<?php
namespace App\Router;

class Request
{
    public function __construct(
        public string $method,
        public string $path,
        public array  $query,
        public array  $body,
        public array  $files,
        public array  $params = []
    ) {}

    public static function capture(): self{
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Strip base path dynamically so routes work under /ads133_portfolio/public or root
        if (\defined('BASE_URL') && BASE_URL !== '') {
            $base = rtrim(BASE_URL, '/');
        } else {
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            $base = in_array($scriptDir, ['/', '.', ''], true) ? '' : rtrim($scriptDir, '/');
        }

        if ($base !== '' && \str_starts_with($uri, $base)) {
            $uri = \substr($uri, \strlen($base)) ?: '/';
        }

        if ($uri === '/index.php') {
            $uri = '/';
        }

        return new self($method, '/' . \ltrim($uri, '/'), $_GET, $_POST, $_FILES);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function withParams(array $params): self
    {
        return new self($this->method, $this->path, $this->query, $this->body, $this->files, $params);
    }
}