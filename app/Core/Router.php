<?php
declare(strict_types=1);

namespace App\Core;

/** Enrutador mínimo con parámetros {nombre}. */
final class Router
{
    /** @var array<int, array{0:string,1:string,2:mixed}> */
    private array $routes = [];

    public function get(string $pattern, mixed $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, mixed $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, mixed $handler): void
    {
        $regex = preg_replace('#\{([a-z_]+)\}#i', '(?P<$1>[^/]+)', rtrim($pattern, '/') ?: '/');
        $this->routes[] = [$method, '#^' . $regex . '$#u', $handler];
    }

    public function dispatch(string $method, string $path): bool
    {
        $path = rtrim($path, '/') ?: '/';
        foreach ($this->routes as [$m, $regex, $handler]) {
            if ($m !== $method || !preg_match($regex, $path, $match)) {
                continue;
            }
            $params = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
            $params = array_map('rawurldecode', $params);
            if (is_array($handler)) {
                [$class, $action] = $handler;
                (new $class())->$action($params);
            } else {
                $handler($params);
            }
            return true;
        }
        return false;
    }
}
