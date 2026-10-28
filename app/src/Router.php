<?php
declare(strict_types=1);

namespace App;

final class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, array $handler): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#u';
        $this->routes[] = [$method, $regex, $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        $allowed = false;
        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if (!preg_match($regex, $path, $m)) {
                continue;
            }
            if ($routeMethod !== $method) {
                $allowed = true;
                continue;
            }
            if ($method === 'POST') {
                Csrf::verify();
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $handler;
            (new $class())->$action($params);
            return;
        }
        throw new HttpException($allowed ? 405 : 404);
    }
}
